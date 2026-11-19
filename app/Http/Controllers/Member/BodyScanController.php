<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BodyScan;
use App\Models\ScanAuditLog;
use App\Services\Ai\BodyScanAnalyzer;
use App\Services\BodyScanService;
use App\Support\MemberStats;
use Illuminate\Http\Request;

class BodyScanController extends Controller
{
    public function index(Request $request, BodyScanService $scans, BodyScanAnalyzer $analyzer)
    {
        $user = $request->user()->load('memberProfile');

        return view('member.scanner.index', [
            'eligibility' => $scans->eligibility($user),
            'aiEnabled' => $analyzer->enabled(),
            'scans' => $user->bodyScans()->latest()->get(),
            'currentWeight' => (new MemberStats($user))->currentWeight(),
            'height' => optional($user->memberProfile)->height_cm,
        ]);
    }

    public function store(Request $request, BodyScanService $scans)
    {
        $data = $request->validate([
            'front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'side' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'back' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'weight_kg' => ['required', 'numeric', 'between:25,300'],
            'height_cm' => ['required', 'numeric', 'between:100,250'],
            'consent' => ['accepted'],
        ], ['consent.accepted' => 'Please confirm you agree to AI analysis of your photos.']);

        // Eligibility, duplicate-photo and concurrency checks all happen inside the service.
        $scan = $scans->accept(
            $request->user(),
            ['front' => $request->file('front'), 'side' => $request->file('side'), 'back' => $request->file('back')],
            (float) $data['weight_kg'],
            (float) $data['height_cm']
        );

        if ($profile = $request->user()->memberProfile) {
            $profile->update(['height_cm' => $data['height_cm']]);
        }

        return redirect()->route('member.scanner.show', $scan);
    }

    public function show(Request $request, BodyScan $scan)
    {
        abort_unless($scan->user_id === $request->user()->id, 404);

        return view('member.scanner.show', [
            'scan' => $scan,
            'history' => ScanAuditLog::where('body_scan_id', $scan->id)->with('actor')->latest('id')->get(),
        ]);
    }

    /** Polled by the result page while analysis runs. */
    public function status(Request $request, BodyScan $scan)
    {
        abort_unless($scan->user_id === $request->user()->id, 404);

        return response()->json(['status' => $scan->status]);
    }

    public function retry(Request $request, BodyScan $scan, BodyScanService $scans)
    {
        abort_unless($scan->user_id === $request->user()->id, 404);
        abort_unless($scan->canRetryAnalysis(), 422, 'This scan cannot be re-analysed. Please contact the front desk.');

        $scans->retryAnalysis($scan, $request->user());

        return redirect()->route('member.scanner.show', $scan);
    }
}
