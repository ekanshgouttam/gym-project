<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateScanComparison;
use App\Models\BodyScan;
use App\Models\ScanComparison;
use App\Services\Ai\BodyScanAnalyzer;
use App\Support\ScanComparisonFacts;
use Illuminate\Http\Request;

/** My Transformation: compare any two accepted scans. Original photos are never modified. */
class TransformationController extends Controller
{
    public function show(Request $request, BodyScanAnalyzer $analyzer)
    {
        $user = $request->user();
        $scans = $user->bodyScans()->accepted()->orderBy('created_at')->get();

        if ($scans->count() < 2) {
            return view('member.transformation', ['scans' => $scans, 'from' => null, 'to' => null]);
        }

        // Default: baseline vs latest. Either can be any accepted scan, in any order.
        $from = $scans->firstWhere('id', (int) $request->query('from')) ?? $scans->first();
        $to = $scans->firstWhere('id', (int) $request->query('to')) ?? $scans->last();
        if ($from->id === $to->id) {
            $to = $scans->last()->id === $from->id ? $scans->first() : $scans->last();
        }
        if ($from->created_at->gt($to->created_at)) {
            [$from, $to] = [$to, $from];
        }

        $comparison = ScanComparison::where('from_scan_id', $from->id)->where('to_scan_id', $to->id)->first();

        return view('member.transformation', [
            'scans' => $scans,
            'from' => $from,
            'to' => $to,
            'facts' => ScanComparisonFacts::for($from, $to),
            'comparison' => $comparison,
            'aiEnabled' => $analyzer->enabled(),
            'limitReached' => $this->generatedToday($user->id) >= ScanComparison::DAILY_LIMIT,
            'trend' => [
                'labels' => $scans->where('status', 'completed')->map(function ($s) { return $s->created_at->format('d M'); })->values()->all(),
                'series' => [['name' => 'AI visual score', 'data' => $scans->where('status', 'completed')->pluck('physique_score')->values()->all()]],
            ],
        ]);
    }

    /** Generate (or regenerate after failure) the AI summary for a pair. */
    public function generate(Request $request, BodyScanAnalyzer $analyzer)
    {
        $user = $request->user();
        $data = $request->validate(['from' => ['required', 'integer'], 'to' => ['required', 'integer', 'different:from']]);

        $from = BodyScan::where('user_id', $user->id)->accepted()->findOrFail($data['from']);
        $to = BodyScan::where('user_id', $user->id)->accepted()->findOrFail($data['to']);
        if ($from->created_at->gt($to->created_at)) {
            [$from, $to] = [$to, $from];
        }

        abort_unless($analyzer->enabled(), 503, 'AI comparisons are not available right now.');

        $existing = ScanComparison::where('from_scan_id', $from->id)->where('to_scan_id', $to->id)->first();
        if ($existing && $existing->status !== 'failed') {
            return redirect()->route('member.transformation', ['from' => $from->id, 'to' => $to->id]);
        }
        if ($this->generatedToday($user->id) >= ScanComparison::DAILY_LIMIT) {
            return back()->withErrors(['compare' => 'You have reached today\'s limit of AI comparisons. Please try again tomorrow.']);
        }

        $comparison = $existing ?: new ScanComparison(['user_id' => $user->id, 'from_scan_id' => $from->id, 'to_scan_id' => $to->id]);
        $comparison->fill(['status' => 'pending', 'error' => null])->save();
        GenerateScanComparison::dispatchAfterResponse($comparison);

        return redirect()->route('member.transformation', ['from' => $from->id, 'to' => $to->id]);
    }

    public function status(Request $request, ScanComparison $comparison)
    {
        abort_unless($comparison->user_id === $request->user()->id, 404);

        return response()->json(['status' => $comparison->status]);
    }

    private function generatedToday(int $userId): int
    {
        return ScanComparison::where('user_id', $userId)->where('updated_at', '>=', now()->startOfDay())->count();
    }
}
