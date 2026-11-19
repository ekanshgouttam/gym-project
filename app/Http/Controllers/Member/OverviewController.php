<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BodyScan;
use App\Support\MemberStats;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->load('memberProfile', 'gym');
        $stats = new MemberStats($user);

        $weights = $user->weightLogs()->orderByDesc('logged_on')->take(12)->get()->sortBy('logged_on')->values();

        return view('member.overview', [
            'user' => $user,
            'profile' => $user->memberProfile,
            'membership' => $user->activeMembership(),
            'pending' => $user->memberships()->with('plan')->where('status', 'pending')->latest()->first(),
            'stats' => $stats,
            'scanEligibility' => $stats->scanEligibility(),
            'latestScan' => $user->bodyScans()->where('status', 'completed')->latest()->first(),
            'weightSeries' => [
                'labels' => $weights->map(function ($w) { return $w->logged_on->format('d M'); })->all(),
                'series' => [['name' => 'Weight', 'data' => $weights->pluck('weight_kg')->all()]],
                'unit' => 'kg',
            ],
            'todayWorkout' => $stats->todaysWorkout(),
            'notifications' => $user->unreadNotifications()->take(4)->get(),
        ]);
    }
}
