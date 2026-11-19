<?php

namespace App\Services;

use App\Jobs\AnalyzeBodyScan;
use App\Models\BodyScan;
use App\Models\BodyScanOverride;
use App\Models\ScanAuditLog;
use App\Models\User;
use App\Models\WeightLog;
use App\Services\Ai\BodyScanAnalyzer;
use App\Services\Vision\PhotoQualityInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place that accepts, corrects and analyses body scans.
 *
 * 30-day rule: the first scan is allowed immediately; each later scan only once
 * INTERVAL_DAYS have passed since the last accepted (non-voided) scan, unless an
 * admin granted a one-time override. Enforced here under a row lock, so two
 * simultaneous uploads can't both get through.
 */
class BodyScanService
{
    private $images;
    private $inspector;
    private $analyzer;

    public function __construct(ImageStore $images, PhotoQualityInspector $inspector, BodyScanAnalyzer $analyzer)
    {
        $this->images = $images;
        $this->inspector = $inspector;
        $this->analyzer = $analyzer;
    }

    /**
     * @return array{eligible: bool, first_scan: bool, next_at: ?Carbon, seconds_left: int, reason: ?string, override: ?BodyScanOverride, last: ?BodyScan}
     */
    public function eligibility(User $member): array
    {
        $last = $member->bodyScans()->accepted()->latest('created_at')->latest('id')->first();
        $result = ['eligible' => false, 'first_scan' => $last === null, 'next_at' => null, 'seconds_left' => 0, 'reason' => null, 'override' => null, 'last' => $last];

        if (! $last) {
            return ['eligible' => true] + $result;
        }

        if (! $member->hasActiveMembership()) {
            return ['reason' => 'An active membership is required for follow-up scans.'] + $result;
        }

        $nextAt = $last->created_at->copy()->addDays(BodyScan::INTERVAL_DAYS);
        $result['next_at'] = $nextAt;

        if ($nextAt->lte(now())) {
            return ['eligible' => true] + $result;
        }

        $override = BodyScanOverride::where('member_id', $member->id)->usable()->latest()->first();
        if ($override) {
            return ['eligible' => true, 'override' => $override] + $result;
        }

        $result['seconds_left'] = now()->diffInSeconds($nextAt);
        $result['reason'] = 'Scans are spaced ' . BodyScan::INTERVAL_DAYS . ' days apart so changes are visible and comparable.';

        return $result;
    }

    /**
     * Validate, store and record a new scan, then queue its AI analysis.
     *
     * @param array<string, UploadedFile> $photos keyed by front/side/back
     */
    public function accept(User $member, array $photos, float $weightKg, ?float $heightCm, bool $baseline = false): BodyScan
    {
        $stored = [];
        try {
            // 1. Computer-vision checks + storage (outside the lock: it's the slow part).
            foreach (BodyScan::VIEWS as $view) {
                if (! isset($photos[$view])) {
                    throw ValidationException::withMessages([$view => ucfirst($view) . ' photo is required.']);
                }
                $stored[$view] = $this->images->storeScanPhoto($photos[$view], 'scans/' . $member->id, $view, $this->inspector);
            }

            $hashes = collect($stored)->map(function ($s) { return $s['original']['sha256']; });
            if ($hashes->unique()->count() !== $hashes->count()) {
                throw ValidationException::withMessages(['front' => 'Front, side and back must be three different photos.']);
            }

            // 2. Lock the member row and re-check the rules against the database.
            $scan = DB::transaction(function () use ($member, $stored, $hashes, $weightKg, $heightCm, $baseline) {
                User::whereKey($member->id)->lockForUpdate()->first();

                $eligibility = $this->eligibility($member);
                if (! $eligibility['eligible']) {
                    $msg = $eligibility['next_at']
                        ? 'Your next scan is available on ' . $eligibility['next_at']->format('d M Y, g:i a') . '.'
                        : ($eligibility['reason'] ?? 'You are not eligible for a scan yet.');
                    throw ValidationException::withMessages(['front' => $msg]);
                }

                $previousHashes = $member->bodyScans()->get()->flatMap->photoHashes();
                if ($hashes->intersect($previousHashes)->isNotEmpty()) {
                    throw ValidationException::withMessages(['front' => 'One of these photos was already used in a previous scan. Please take new photos.']);
                }

                $scan = BodyScan::create([
                    'user_id' => $member->id,
                    'is_baseline' => $baseline || $eligibility['first_scan'],
                    'front_path' => $stored['front']['display_path'],
                    'side_path' => $stored['side']['display_path'],
                    'back_path' => $stored['back']['display_path'],
                    'originals' => collect($stored)->map(function ($s) { return $s['original']; })->all(),
                    'quality' => collect($stored)->map(function ($s) { return $s['quality']; })->all(),
                    'weight_kg' => $weightKg,
                    'height_cm' => $heightCm ?? optional($member->memberProfile)->height_cm,
                    'status' => 'pending',
                ]);

                ScanAuditLog::record($member, $scan->is_baseline ? 'baseline_created' : 'uploaded', $member, $scan, null, [
                    'weight_kg' => $weightKg,
                    'warnings' => collect($stored)->flatMap(function ($s) { return $s['quality']['warnings']; })->values()->all(),
                ]);

                if ($eligibility['override']) {
                    $eligibility['override']->update(['used_at' => now(), 'used_for_scan_id' => $scan->id]);
                    ScanAuditLog::record($member, 'override_used', $member, $scan, $eligibility['override']->reason);
                }

                WeightLog::updateOrCreate(
                    ['user_id' => $member->id, 'logged_on' => today()->toDateString()],
                    ['weight_kg' => $weightKg, 'note' => $scan->is_baseline ? 'Baseline scan' : 'Body scan']
                );

                return $scan;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $s) {
                $this->images->delete($s['display_path']);
                $this->images->delete($s['original']['path']);
            }
            throw $e;
        }

        $this->queueAnalysis($scan);

        return $scan;
    }

    public function queueAnalysis(BodyScan $scan): void
    {
        if (! $this->analyzer->enabled()) {
            $scan->update(['status' => 'failed', 'error' => 'AI analysis is not available right now. Your photos are saved and can be analysed later.']);

            return;
        }

        $scan->update(['status' => 'pending', 'error' => null]);
        AnalyzeBodyScan::dispatchAfterResponse($scan);
    }

    /** Members get MAX_ANALYSIS_ATTEMPTS tries; an admin retry resets the counter. */
    public function retryAnalysis(BodyScan $scan, User $actor, ?string $reason = null, bool $resetAttempts = false): void
    {
        if ($resetAttempts) {
            $scan->update(['analysis_attempts' => 0]);
        }
        ScanAuditLog::record($scan->user, 'analysis_retried', $actor, $scan, $reason);
        $this->queueAnalysis($scan);
    }

    public function void(BodyScan $scan, User $actor, string $reason): void
    {
        $scan->forceFill(['voided_at' => now(), 'voided_by' => $actor->id, 'void_reason' => $reason])->save();
        ScanAuditLog::record($scan->user, 'voided', $actor, $scan, $reason);
    }

    public function restore(BodyScan $scan, User $actor, string $reason): void
    {
        $scan->forceFill(['voided_at' => null, 'voided_by' => null, 'void_reason' => null])->save();
        ScanAuditLog::record($scan->user, 'restored', $actor, $scan, $reason);
    }

    public function correct(BodyScan $scan, User $actor, array $values, string $reason): void
    {
        $changes = [];
        foreach (['weight_kg', 'height_cm'] as $field) {
            if (array_key_exists($field, $values) && $values[$field] !== null && (float) $values[$field] !== (float) $scan->{$field}) {
                $changes[$field] = ['from' => $scan->{$field}, 'to' => (float) $values[$field]];
                $scan->{$field} = $values[$field];
            }
        }
        if (! $changes) {
            return;
        }
        $scan->save();
        ScanAuditLog::record($scan->user, 'corrected', $actor, $scan, $reason, $changes);
    }

    public function grantOverride(User $member, User $actor, string $reason, int $days = 7): BodyScanOverride
    {
        $override = BodyScanOverride::create([
            'member_id' => $member->id,
            'granted_by' => $actor->id,
            'reason' => $reason,
            'expires_at' => now()->addDays($days),
        ]);
        ScanAuditLog::record($member, 'override_granted', $actor, null, $reason, ['expires_at' => $override->expires_at->toIso8601String()]);

        return $override;
    }
}
