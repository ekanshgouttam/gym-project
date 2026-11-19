<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BodyScan;
use App\Models\Enquiry;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Support\PlatformStats;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Body transformation overview and gym reports for the owner. */
class InsightController extends Controller
{
    public function transformation(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $filter = $request->query('filter', 'all');

        $members = User::role(Roles::MEMBER)->where('gym_id', $gymId)->where('status', 'active')
            ->with('memberProfile:id,user_id,member_code,trainer_id', 'memberProfile.trainer:id,name')
            ->withCount(['bodyScans as scans_count' => function ($q) { $q->accepted(); }])
            ->orderBy('name')->get();

        $latest = BodyScan::accepted()->whereIn('user_id', $members->pluck('id'))
            ->whereIn('id', BodyScan::accepted()->groupBy('user_id')->select(DB::raw('MAX(id)')))
            ->get()->keyBy('user_id');

        $rows = $members->map(function ($m) use ($latest) {
            $last = $latest->get($m->id);
            $next = $last ? $last->created_at->copy()->addDays(BodyScan::INTERVAL_DAYS) : null;

            return [
                'member' => $m,
                'last' => $last,
                'next' => $next,
                'state' => ! $last ? 'no_baseline' : ($next->isPast() ? 'due' : 'waiting'),
            ];
        });

        $counts = $rows->countBy('state');
        if ($filter !== 'all') {
            $rows = $rows->where('state', $filter)->values();
        }

        return view('gym-admin.transformation', [
            'rows' => $rows,
            'filter' => $filter,
            'counts' => $counts,
            'thisMonth' => BodyScan::accepted()->whereIn('user_id', $members->pluck('id'))->where('created_at', '>=', now()->startOfMonth())->count(),
            'avgScore' => BodyScan::accepted()->where('status', 'completed')->whereIn('user_id', $members->pluck('id'))->where('created_at', '>=', now()->subDays(90))->avg('physique_score'),
        ]);
    }

    public function reports(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $payments = Payment::where('status', 'paid')->whereHas('membership', function ($q) use ($gymId) { $q->where('gym_id', $gymId); });
        $revenue = PlatformStats::monthly(clone $payments, 'paid_at', 'SUM(amount_paise) / 100', 12);
        $joins = PlatformStats::monthly(User::role(Roles::MEMBER)->where('gym_id', $gymId), 'users.created_at', 'COUNT(*)', 12);
        $visits = PlatformStats::monthly(Attendance::where('gym_id', $gymId), 'checked_in_at', 'COUNT(*)', 12);

        $activeIds = Membership::where('gym_id', $gymId)->where('status', 'active')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->pluck('user_id')->unique();
        $memberCount = User::role(Roles::MEMBER)->where('gym_id', $gymId)->count();

        // Renewal rate: of memberships that ended in the last 90 days, how many members started another one.
        $ended = Membership::where('gym_id', $gymId)->whereIn('status', ['active', 'expired'])->whereBetween('ends_on', [today()->subDays(90), today()->subDay()])->get(['id', 'user_id', 'ends_on']);
        $renewed = $ended->filter(function ($m) {
            return Membership::where('user_id', $m->user_id)->where('id', '>', $m->id)->whereIn('status', ['active', 'expired'])->exists();
        })->count();

        $enquiries = Enquiry::where('gym_id', $gymId)->where('created_at', '>=', now()->subDays(90));

        return view('gym-admin.reports', [
            'kpis' => [
                'revenue30' => (int) round((clone $payments)->where('paid_at', '>=', now()->subDays(30))->sum('amount_paise') / 100),
                'active' => $activeIds->count(),
                'inactive' => max(0, $memberCount - $activeIds->count()),
                'renewal' => $ended->count() ? (int) round($renewed / $ended->count() * 100) : null,
                'visits30' => Attendance::where('gym_id', $gymId)->where('checked_in_at', '>=', now()->subDays(30))->count(),
                'enquiryRate' => ($n = (clone $enquiries)->count()) ? (int) round((clone $enquiries)->where('status', 'converted')->count() / $n * 100) : null,
            ],
            'revenueChart' => ['labels' => $revenue['labels'], 'unit' => '', 'series' => [['name' => 'Fees collected (₹)', 'data' => $revenue['values']]]],
            'growthChart' => ['labels' => $joins['labels'], 'unit' => '', 'series' => [['name' => 'New members', 'data' => $joins['values']], ['name' => 'Visits', 'data' => $visits['values']]]],
            'plans' => Membership::where('gym_id', $gymId)->whereIn('status', ['active', 'expired'])->where('created_at', '>=', now()->subYear())
                ->groupBy('membership_plan_id')->selectRaw('membership_plan_id, COUNT(*) as n, SUM(amount) as revenue')->with('plan:id,name')->orderByDesc('n')->get(),
            'busiest' => Attendance::where('gym_id', $gymId)->where('checked_in_at', '>=', now()->subDays(60))
                ->groupBy('dow')->selectRaw('DAYOFWEEK(checked_in_at) as dow, COUNT(*) as n')->pluck('n', 'dow'),
            'atRisk' => User::role(Roles::MEMBER)->where('gym_id', $gymId)->whereIn('id', $activeIds)
                ->whereDoesntHave('attendances', function ($q) { $q->where('checked_in_at', '>=', now()->subDays(14)); })
                ->orderBy('name')->take(10)->get(['id', 'name', 'phone']),
        ]);
    }

    public function exportMembers(Request $request): StreamedResponse
    {
        $gymId = $request->user()->gym_id;
        $members = User::role(Roles::MEMBER)->where('gym_id', $gymId)->with('memberProfile', 'memberProfile.trainer:id,name')->orderBy('name')->get();

        return response()->streamDownload(function () use ($members) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member ID', 'Name', 'Email', 'Mobile', 'Status', 'Plan', 'Membership ends', 'Joined', 'Joined via', 'Trainer']);
            foreach ($members as $m) {
                $active = $m->activeMembership();
                fputcsv($out, [
                    optional($m->memberProfile)->member_code, $m->name, $m->email, $m->phone, $m->status,
                    $active ? $active->plan->name : '', $active ? $active->ends_on->toDateString() : '', $m->created_at->toDateString(),
                    \App\Models\MemberProfile::SOURCES[optional($m->memberProfile)->source] ?? '', optional(optional($m->memberProfile)->trainer)->name,
                ]);
            }
            fclose($out);
        }, 'members-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
