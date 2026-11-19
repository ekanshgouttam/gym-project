<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Support\MemberStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $month = $this->month($request->query('month'));

        $visits = $user->attendances()->with('gym')
            ->whereBetween('checked_in_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderByDesc('checked_in_at')
            ->get();

        $stats = new MemberStats($user);
        $durations = $visits->map->durationMinutes()->filter();

        return view('member.attendance', [
            'month' => $month,
            'visits' => $visits,
            'visitDays' => $visits->map(function ($v) { return $v->checked_in_at->day; })->unique()->flip(),
            'avgMinutes' => $durations->count() ? (int) round($durations->avg()) : null,
            'streak' => $stats->weekStreak(),
            'totalVisits' => $user->attendances()->count(),
            'prev' => $month->copy()->subMonth(),
            'next' => $month->copy()->addMonth()->lte(now()->startOfMonth()) ? $month->copy()->addMonth() : null,
        ]);
    }

    private function month(?string $value): Carbon
    {
        try {
            $m = $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : now()->startOfMonth();
        } catch (\Exception $e) {
            $m = now()->startOfMonth();
        }

        return $m->gt(now()) ? now()->startOfMonth() : $m;
    }
}
