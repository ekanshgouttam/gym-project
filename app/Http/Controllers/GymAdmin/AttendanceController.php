<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Support\PlatformStats;
use App\Support\Roles;
use Illuminate\Http\Request;

/** Front-desk check-in / check-out and today's attendance. */
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $date = $request->filled('date') ? \Illuminate\Support\Carbon::parse($request->query('date')) : today();

        $results = collect();
        if ($request->filled('q')) {
            $term = '%' . trim($request->q) . '%';
            $results = User::role(Roles::MEMBER)->where('gym_id', $gymId)
                ->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)->orWhere('phone', 'like', $term)
                        ->orWhereHas('memberProfile', function ($p) use ($term) { $p->where('member_code', 'like', $term); });
                })
                ->with('memberProfile:id,user_id,member_code')
                ->orderBy('name')->take(10)->get()
                ->each(function ($u) { $u->setAttribute('active_membership', $u->activeMembership()); });
        }

        $day = Attendance::where('gym_id', $gymId)->whereDate('checked_in_at', $date);

        return view('gym-admin.attendance', [
            'date' => $date,
            'visits' => (clone $day)->with('user:id,name', 'user.memberProfile:id,user_id,member_code')->latest('checked_in_at')->get(),
            'inside' => (clone $day)->whereNull('checked_out_at')->count(),
            'results' => $results,
            'week' => PlatformStats::monthly(Attendance::where('gym_id', $gymId), 'checked_in_at', 'COUNT(*)'),
            'byHour' => Attendance::where('gym_id', $gymId)->where('checked_in_at', '>=', now()->subDays(30))
                ->groupBy('h')->selectRaw('HOUR(checked_in_at) as h, COUNT(*) as n')->pluck('n', 'h'),
        ]);
    }

    public function checkIn(Request $request, User $member)
    {
        abort_unless($member->gym_id === $request->user()->gym_id && $member->hasRole(Roles::MEMBER), 404);

        $open = Attendance::where('user_id', $member->id)->whereDate('checked_in_at', today())->whereNull('checked_out_at')->first();
        if ($open) {
            return back()->with('status', "{$member->name} is already checked in (since {$open->checked_in_at->format('H:i')}).");
        }

        Attendance::create(['user_id' => $member->id, 'gym_id' => $member->gym_id, 'checked_in_at' => now(), 'method' => 'desk']);
        $warning = $member->hasActiveMembership() ? '' : ' Note: no active membership — collect the fee.';

        return redirect()->route('gym-admin.attendance.index')->with('status', "{$member->name} checked in at " . now()->format('H:i') . '.' . $warning);
    }

    public function checkOut(Request $request, Attendance $attendance)
    {
        abort_unless($attendance->gym_id === $request->user()->gym_id, 404);
        if (! $attendance->checked_out_at) {
            $attendance->update(['checked_out_at' => now()]);
        }

        return back()->with('status', 'Checked out.');
    }
}
