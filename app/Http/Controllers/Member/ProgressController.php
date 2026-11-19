<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BodyMeasurement;
use App\Models\WeightLog;
use App\Support\MemberStats;
use Illuminate\Http\Request;

/** Progress History, Weight Tracking and Body Measurements. (My Transformation: TransformationController.) */
class ProgressController extends Controller
{
    public function history(Request $request)
    {
        $user = $request->user();

        // One merged, date-sorted timeline across all tracking sources. Every accepted scan stays here permanently.
        $events = collect()
            ->concat($user->bodyScans()->get()->map(function ($s) {
                if ($s->isVoided()) {
                    $detail = 'Voided by gym staff: ' . $s->void_reason;
                } elseif ($s->status === 'completed') {
                    $detail = 'AI visual score ' . $s->physique_score . '/100 · ' . $s->weight_kg . ' kg';
                } else {
                    $detail = $s->weight_kg . ' kg · analysis ' . $s->status;
                }

                return ['date' => $s->created_at, 'type' => 'scan', 'title' => $s->is_baseline ? 'Baseline body scan' : 'AI body scan', 'detail' => $detail, 'url' => route('member.scanner.show', $s)];
            }))
            ->concat($user->weightLogs()->get()->map(function ($w) {
                return ['date' => $w->logged_on, 'type' => 'weight', 'title' => 'Weight logged', 'detail' => "{$w->weight_kg} kg" . ($w->note ? " · {$w->note}" : ''), 'url' => null];
            }))
            ->concat($user->bodyMeasurements()->get()->map(function ($m) {
                $parts = collect(BodyMeasurement::PARTS)->map(function ($label, $k) use ($m) {
                    return $m->{$k . '_cm'} ? "{$label} {$m->{$k . '_cm'}}" : null;
                })->filter()->implode(' · ');

                return ['date' => $m->measured_on, 'type' => 'measurement', 'title' => 'Measurements', 'detail' => $parts . ' cm', 'url' => null];
            }))
            ->sortByDesc('date')
            ->values()
            ->take(150);

        return view('member.history', [
            'events' => $events,
            'grouped' => $events->groupBy(function ($e) { return $e['date']->format('F Y'); }),
        ]);
    }

    public function weight(Request $request)
    {
        $user = $request->user()->load('memberProfile');
        $logs = $user->weightLogs()->orderBy('logged_on')->get();

        return view('member.weight', [
            'logs' => $logs->sortByDesc('logged_on')->values(),
            'stats' => new MemberStats($user),
            'profile' => $user->memberProfile,
            'series' => [
                'labels' => $logs->map(function ($w) { return $w->logged_on->format('d M'); })->all(),
                'series' => [['name' => 'Weight', 'data' => $logs->pluck('weight_kg')->all()]],
                'unit' => 'kg',
                'target' => optional($user->memberProfile)->target_weight_kg,
            ],
        ]);
    }

    public function storeWeight(Request $request)
    {
        $data = $request->validate([
            'logged_on' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'between:25,300'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        WeightLog::updateOrCreate(
            ['user_id' => $request->user()->id, 'logged_on' => $data['logged_on']],
            ['weight_kg' => $data['weight_kg'], 'note' => $data['note'] ?? null]
        );

        return back()->with('status', 'Weight saved.');
    }

    public function destroyWeight(Request $request, WeightLog $log)
    {
        abort_unless($log->user_id === $request->user()->id, 404);
        $log->delete();

        return back()->with('status', 'Entry removed.');
    }

    public function measurements(Request $request)
    {
        $rows = $request->user()->bodyMeasurements()->orderBy('measured_on')->get();

        return view('member.measurements', [
            'rows' => $rows->sortByDesc('measured_on')->values(),
            'parts' => BodyMeasurement::PARTS,
            'series' => [
                'labels' => $rows->map(function ($r) { return $r->measured_on->format('d M'); })->all(),
                'series' => collect(['chest', 'waist', 'hips', 'arms'])->map(function ($p) use ($rows) {
                    return ['name' => BodyMeasurement::PARTS[$p], 'data' => $rows->pluck($p . '_cm')->all()];
                })->all(),
                'unit' => 'cm',
            ],
        ]);
    }

    public function storeMeasurement(Request $request)
    {
        $rules = ['measured_on' => ['required', 'date', 'before_or_equal:today'], 'body_fat_pct' => ['nullable', 'numeric', 'between:2,70']];
        foreach (array_keys(BodyMeasurement::PARTS) as $part) {
            $rules[$part . '_cm'] = ['nullable', 'numeric', 'between:10,250'];
        }
        $data = $request->validate($rules);

        $values = collect($data)->except('measured_on');
        if ($values->filter(function ($v) { return $v !== null && $v !== ''; })->isEmpty()) {
            return back()->withInput()->withErrors(['chest_cm' => 'Enter at least one measurement.']);
        }

        BodyMeasurement::updateOrCreate(
            ['user_id' => $request->user()->id, 'measured_on' => $data['measured_on']],
            $values->all()
        );

        return back()->with('status', 'Measurements saved.');
    }

    public function destroyMeasurement(Request $request, BodyMeasurement $measurement)
    {
        abort_unless($measurement->user_id === $request->user()->id, 404);
        $measurement->delete();

        return back()->with('status', 'Entry removed.');
    }
}
