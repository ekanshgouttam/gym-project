<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\JoinVisit;
use App\Models\MemberProfile;
use App\Support\PlatformStats;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

/** Branch registration QR code: view/print, download SVG, rotate token. */
class GymQrController extends Controller
{
    public function show(Request $request, Gym $gym, QrCodeService $qr)
    {
        $this->authorizeGym($request, $gym);

        return view('manage.gyms.qr', ['gym' => $gym, 'svg' => $qr->svg($gym->qrUrl())] + $this->tracking($gym));
    }

    public function download(Request $request, Gym $gym, QrCodeService $qr)
    {
        $this->authorizeGym($request, $gym);

        return response($qr->svg($gym->qrUrl(), 1024), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $gym->slug . '-join-qr.svg"',
        ]);
    }

    /** Invalidates printed codes, e.g. if a QR poster was misused. */
    public function rotate(Request $request, Gym $gym)
    {
        $this->authorizeGym($request, $gym);
        $gym->forceFill(['join_token' => Gym::newJoinToken()])->save();

        return back()->with('status', 'A new QR code was generated. Old printed codes no longer work.');
    }

    /** QR scans, registrations by source and conversion for the QR page. */
    private function tracking(Gym $gym): array
    {
        $since = now()->subDays(30);
        $profiles = function () use ($gym) {
            return MemberProfile::whereHas('user', function ($q) use ($gym) { $q->where('gym_id', $gym->id); });
        };

        $scans30 = JoinVisit::where('gym_id', $gym->id)->where('source', 'qr')->where('created_at', '>=', $since)->count();
        $qrRegs30 = $profiles()->where('source', 'qr')->where('created_at', '>=', $since)->count();

        $scanSeries = PlatformStats::monthly(JoinVisit::where('gym_id', $gym->id)->where('source', 'qr'), 'created_at', 'COUNT(*)');
        $regSeries = PlatformStats::monthly($profiles()->where('source', 'qr'), 'member_profiles.created_at', 'COUNT(*)');

        return [
            'qrStats' => [
                'scans30' => $scans30,
                'regs30' => $qrRegs30,
                'conversion' => $scans30 ? min(100, (int) round($qrRegs30 / $scans30 * 100)) : null,
                'linkVisits30' => JoinVisit::where('gym_id', $gym->id)->where('source', 'link')->where('created_at', '>=', $since)->count(),
            ],
            'bySource' => $profiles()->groupBy('source')->selectRaw('source, COUNT(*) as total')->pluck('total', 'source'),
            'qrChart' => [
                'labels' => $scanSeries['labels'],
                'unit' => '',
                'series' => [['name' => 'QR scans', 'data' => $scanSeries['values']], ['name' => 'Registrations from QR', 'data' => $regSeries['values']]],
            ],
            'recentQr' => $profiles()->where('source', 'qr')->with('user:id,name,email')->latest()->take(8)->get(),
        ];
    }

    private function authorizeGym(Request $request, Gym $gym): void
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || ($actor->can('manage members') && $actor->gym_id === $gym->id), 403);
    }
}
