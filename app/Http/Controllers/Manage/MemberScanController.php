<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\BodyScan;
use App\Models\BodyScanOverride;
use App\Models\ScanAuditLog;
use App\Models\User;
use App\Services\BodyScanService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-authorised scan corrections. Every action requires a reason and is written to the
 * append-only scan audit log. Scans are never deleted; voided scans stay in history.
 */
class MemberScanController extends Controller
{
    public function index(Request $request, User $member, BodyScanService $scans)
    {
        $this->authorizeMember($request, $member);

        return view('manage.scans.index', [
            'member' => $member->load('memberProfile', 'gym'),
            'scans' => $member->bodyScans()->with('voidedBy')->latest()->get(),
            'eligibility' => $scans->eligibility($member),
            'overrides' => BodyScanOverride::where('member_id', $member->id)->with('grantedBy')->latest()->take(5)->get(),
            'audit' => ScanAuditLog::where('member_id', $member->id)->with('actor', 'scan')->latest('id')->paginate(25),
        ]);
    }

    public function void(Request $request, BodyScan $scan, BodyScanService $scans)
    {
        $this->authorizeMember($request, $scan->user);
        abort_if($scan->isVoided(), 422, 'This scan is already voided.');
        $scans->void($scan, $request->user(), $this->reason($request));

        return back()->with('status', 'Scan voided. It stays in the member\'s history but no longer counts toward the 30-day rule.');
    }

    public function restore(Request $request, BodyScan $scan, BodyScanService $scans)
    {
        $this->authorizeMember($request, $scan->user);
        abort_unless($scan->isVoided(), 422);
        $scans->restore($scan, $request->user(), $this->reason($request));

        return back()->with('status', 'Scan restored.');
    }

    public function correct(Request $request, BodyScan $scan, BodyScanService $scans)
    {
        $this->authorizeMember($request, $scan->user);
        $data = $request->validate([
            'weight_kg' => ['nullable', 'numeric', 'between:25,300'],
            'height_cm' => ['nullable', 'numeric', 'between:100,250'],
        ]);
        $scans->correct($scan, $request->user(), $data, $this->reason($request));

        return back()->with('status', 'Scan details corrected.');
    }

    public function retry(Request $request, BodyScan $scan, BodyScanService $scans)
    {
        $this->authorizeMember($request, $scan->user);
        abort_if($scan->isVoided(), 422);
        $scans->retryAnalysis($scan, $request->user(), $this->reason($request), true);

        return back()->with('status', 'AI analysis re-queued.');
    }

    public function override(Request $request, User $member, BodyScanService $scans)
    {
        $this->authorizeMember($request, $member);
        $data = $request->validate(['days' => ['required', 'integer', 'between:1,14']]);
        $scans->grantOverride($member, $request->user(), $this->reason($request), (int) $data['days']);

        return back()->with('status', "{$member->name} can upload one scan early within {$data['days']} days.");
    }

    /** The untouched uploaded file (with its original metadata). Downloads are audited. */
    public function original(Request $request, BodyScan $scan, string $view)
    {
        $this->authorizeMember($request, $scan->user);
        $original = ($scan->originals ?? [])[$view] ?? null;
        abort_unless($original && Storage::disk('local')->exists($original['path']), 404);

        ScanAuditLog::record($scan->user, 'original_downloaded', $request->user(), $scan, null, ['view' => $view]);

        return Storage::disk('local')->download($original['path'], "scan-{$scan->id}-{$view}." . pathinfo($original['path'], PATHINFO_EXTENSION));
    }

    private function reason(Request $request): string
    {
        return $request->validate(['reason' => ['required', 'string', 'min:5', 'max:250']])['reason'];
    }

    private function authorizeMember(Request $request, User $member): void
    {
        $actor = $request->user();
        abort_unless($member->hasRole(Roles::MEMBER), 404);
        abort_unless($actor->isSuperAdmin() || ($actor->can('manage members') && $actor->gym_id !== null && $actor->gym_id === $member->gym_id), 403);
    }
}
