<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPayment;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\PlatformStats;
use App\Support\Roles;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** /dashboard — send each user to the dashboard for their role. */
    public function redirect(Request $request)
    {
        $user = $request->user();

        if (! $user->primaryRole()) {
            abort(403, 'Your account has no role assigned. Please contact an administrator.');
        }

        return redirect()->route($user->dashboardRoute());
    }

    public function superAdmin()
    {
        $roleCounts = $this->roleCounts();
        $monthStart = now()->startOfMonth();
        $settings = PlatformSetting::values();
        $aiCost = PlatformStats::aiCostUsd($monthStart);
        $subs = GymSubscription::groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status');

        return view('dashboards.super-admin', [
            'stats' => [
                ['label' => 'Registered gyms', 'value' => Gym::count(), 'hint' => Gym::where('created_at', '>=', $monthStart)->count() . ' new this month'],
                ['label' => 'Active gyms', 'value' => Gym::where('is_active', true)->count(), 'hint' => ($subs['active'] ?? 0) . ' paying · ' . ($subs['trial'] ?? 0) . ' on trial'],
                ['label' => 'Active members', 'value' => PlatformStats::activeMembers(), 'hint' => ($roleCounts[Roles::MEMBER] ?? 0) . ' registered in total'],
                ['label' => 'MRR', 'value' => PlatformStats::mrr(), 'prefix' => '₹', 'hint' => 'from paid subscriptions'],
                ['label' => 'Revenue this month', 'value' => PlatformStats::subscriptionRevenue($monthStart), 'prefix' => '₹', 'hint' => 'subscription payments received'],
                ['label' => 'AI cost this month', 'value' => $aiCost, 'prefix' => '$', 'decimals' => 2, 'hint' => '≈ ₹' . number_format($aiCost * $settings['usd_to_inr']) . ' of $' . number_format($settings['ai_monthly_budget_usd']) . ' budget'],
                ['label' => 'Past-due subscriptions', 'value' => ($subs['past_due'] ?? 0) + ($subs['expired'] ?? 0), 'hint' => 'need follow-up'],
                ['label' => 'Open support requests', 'value' => SupportRequest::unresolved()->count(), 'hint' => SupportRequest::unresolved()->whereIn('priority', ['high', 'urgent'])->count() . ' high priority'],
            ],
            'revenue' => [
                'labels' => ($sub = PlatformStats::monthly(SubscriptionPayment::where('status', 'paid'), 'paid_at', 'SUM(amount)'))['labels'],
                'unit' => '',
                'series' => [['name' => 'Subscription revenue (₹)', 'data' => $sub['values']]],
            ],
            'gyms' => Gym::with('subscription.plan')->withCount('users')->latest()->take(6)->get(),
            'tickets' => SupportRequest::with('gym:id,name')->unresolved()->latest()->take(5)->get(),
            'recentUsers' => User::with('roles', 'gym')->latest()->take(6)->get(),
            'growth' => $this->signupSeries(),
        ]);
    }

    public function gymAdmin(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $roleCounts = $this->roleCounts($gymId);

        $activeMembers = \App\Models\Membership::where('gym_id', $gymId)->where('status', 'active')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->distinct()->count('user_id');

        return view('dashboards.gym-admin', [
            'gym' => $request->user()->gym,
            'stats' => [
                ['label' => 'Active members', 'value' => $activeMembers, 'hint' => ($roleCounts[Roles::MEMBER] ?? 0) . ' registered · ' . ($roleCounts[Roles::TRAINER] ?? 0) . ' trainers'],
                ['label' => 'Check-ins today', 'value' => \App\Models\Attendance::where('gym_id', $gymId)->whereDate('checked_in_at', today())->count(), 'hint' => \App\Models\Attendance::where('gym_id', $gymId)->whereDate('checked_in_at', today())->whereNull('checked_out_at')->count() . ' inside now'],
                ['label' => 'Fees collected this month', 'prefix' => '₹', 'value' => (int) round(\App\Models\Payment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->whereHas('membership', function ($q) use ($gymId) { $q->where('gym_id', $gymId); })->sum('amount_paise') / 100), 'hint' => now()->format('F')],
                ['label' => 'New this month', 'value' => User::role(Roles::MEMBER)->where('gym_id', $gymId)->where('created_at', '>=', now()->startOfMonth())->count(), 'hint' => \App\Models\Enquiry::where('gym_id', $gymId)->where('created_at', '>=', now()->startOfMonth())->count() . ' enquiries'],
            ],
            'expiring' => \App\Models\Membership::with('user:id,name', 'plan:id,name')->where('gym_id', $gymId)->where('status', 'active')
                ->whereBetween('ends_on', [today(), today()->addDays(7)])->orderBy('ends_on')->take(6)->get(),
            'followUps' => \App\Models\Enquiry::where('gym_id', $gymId)->open()->whereDate('next_follow_up_on', '<=', today())->orderBy('next_follow_up_on')->take(6)->get(),
            'recentUsers' => User::with('roles')->where('gym_id', $gymId)->latest()->take(8)->get(),
            'growth' => $this->signupSeries($gymId),
        ]);
    }

    public function trainer(Request $request)
    {
        $gymId = $request->user()->gym_id;

        return view('dashboards.trainer', [
            'members' => User::role(Roles::MEMBER)->where('gym_id', $gymId)->where('status', 'active')->latest()->take(10)->get(),
        ]);
    }


    /** Count of users per role, optionally scoped to one gym. */
    private function roleCounts(?int $gymId = null): array
    {
        return User::query()
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->when($gymId, function ($q) use ($gymId) {
                $q->where('users.gym_id', $gymId);
            })
            ->groupBy('roles.name')
            ->selectRaw('roles.name, COUNT(*) as total')
            ->pluck('total', 'name')
            ->all();
    }

    /** Sign-ups per month for the last 6 months, for the growth chart. */
    private function signupSeries(?int $gymId = null): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $rows = User::query()
            ->where('created_at', '>=', $start)
            ->when($gymId, function ($q) use ($gymId) {
                $q->where('gym_id', $gymId);
            })
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $series = ['labels' => [], 'values' => []];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $series['labels'][] = $month->format('M');
            $series['values'][] = (int) ($rows[$month->format('Y-m')] ?? 0);
        }

        return $series;
    }
}
