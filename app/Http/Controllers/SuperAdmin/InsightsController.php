<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Gym;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\PlatformStats;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Read-only platform reports: active members, revenue, AI usage and cost. */
class InsightsController extends Controller
{
    public function members()
    {
        $active = PlatformStats::activeMembersByGym();
        $registered = User::role(Roles::MEMBER)->groupBy('gym_id')->selectRaw('gym_id, COUNT(*) as total')->pluck('total', 'gym_id');
        $newThisMonth = User::role(Roles::MEMBER)->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('gym_id')->selectRaw('gym_id, COUNT(*) as total')->pluck('total', 'gym_id');

        $rows = Gym::with('subscription.plan')->orderBy('name')->get()->map(function (Gym $gym) use ($active, $registered, $newThisMonth) {
            $limit = optional(optional($gym->subscription)->plan)->member_limit;
            $count = (int) ($active[$gym->id] ?? 0);

            return [
                'gym' => $gym,
                'registered' => (int) ($registered[$gym->id] ?? 0),
                'active' => $count,
                'new' => (int) ($newThisMonth[$gym->id] ?? 0),
                'limit' => $limit,
                'usage' => $limit ? min(100, (int) round($count / $limit * 100)) : null,
            ];
        })->sortByDesc('active')->values();

        return view('super-admin.members', [
            'rows' => $rows,
            'totals' => [
                'registered' => $rows->sum('registered'),
                'active' => $rows->sum('active'),
                'new' => $rows->sum('new'),
            ],
            'growth' => PlatformStats::monthly(User::role(Roles::MEMBER)->where('users.status', '!=', 'applicant'), 'users.created_at', 'COUNT(*)'),
        ]);
    }

    public function revenue()
    {
        $monthStart = now()->startOfMonth();
        $lastMonth = $monthStart->copy()->subMonth();

        $subscriptionSeries = PlatformStats::monthly(SubscriptionPayment::where('status', 'paid'), 'paid_at', 'SUM(amount)');
        $memberSeries = PlatformStats::monthly(Payment::where('status', 'paid'), 'paid_at', 'SUM(amount_paise) / 100');

        $byGym = SubscriptionPayment::where('status', 'paid')
            ->groupBy('gym_id')
            ->selectRaw('gym_id, SUM(amount) as total, MAX(paid_at) as last_paid')
            ->with('gym:id,name')
            ->orderByDesc('total')
            ->get();

        return view('super-admin.revenue', [
            'stats' => [
                'mrr' => PlatformStats::mrr(),
                'thisMonth' => PlatformStats::subscriptionRevenue($monthStart),
                'lastMonth' => PlatformStats::subscriptionRevenue($lastMonth, $monthStart),
                'allTime' => (int) SubscriptionPayment::where('status', 'paid')->sum('amount'),
                'memberThisMonth' => PlatformStats::memberRevenue($monthStart),
                'aiCostInr' => PlatformStats::aiCostUsd($monthStart) * PlatformSetting::get('usd_to_inr'),
            ],
            'chart' => [
                'labels' => $subscriptionSeries['labels'],
                'unit' => '',
                'series' => [
                    ['name' => 'Subscription revenue (₹)', 'data' => $subscriptionSeries['values']],
                    ['name' => 'Member payments via FlexFit (₹)', 'data' => $memberSeries['values']],
                ],
            ],
            'byGym' => $byGym,
        ]);
    }

    public function ai(Request $request)
    {
        $monthStart = now()->startOfMonth();
        $settings = PlatformSetting::values();
        $thisMonth = AiUsageLog::where('created_at', '>=', $monthStart);

        $costSeries = PlatformStats::monthly(AiUsageLog::query(), 'created_at', 'SUM(cost_usd)');
        $callSeries = PlatformStats::monthly(AiUsageLog::query(), 'created_at', 'COUNT(*)');

        $byGym = AiUsageLog::where('created_at', '>=', $monthStart)
            ->groupBy('gym_id')
            ->selectRaw("gym_id, COUNT(*) as calls, SUM(kind = 'scan_analysis') as scans, SUM(kind = 'scan_comparison') as comparisons, SUM(cost_usd) as cost, SUM(success = 0) as failed")
            ->with('gym.subscription.plan')
            ->orderByDesc('cost')
            ->get();

        $cost = (float) (clone $thisMonth)->sum('cost_usd');

        return view('super-admin.ai', [
            'stats' => [
                'calls' => (clone $thisMonth)->count(),
                'failed' => (clone $thisMonth)->where('success', false)->count(),
                'tokens' => (int) (clone $thisMonth)->sum(DB::raw('input_tokens + output_tokens + cache_write_tokens + cache_read_tokens')),
                'cost' => $cost,
                'budget' => (float) $settings['ai_monthly_budget_usd'],
                'budgetUsed' => $settings['ai_monthly_budget_usd'] > 0 ? min(100, (int) round($cost / $settings['ai_monthly_budget_usd'] * 100)) : null,
                'avgCost' => (clone $thisMonth)->where('success', true)->avg('cost_usd'),
            ],
            'rate' => (float) $settings['usd_to_inr'],
            'enabled' => $settings['ai_scans_enabled'] && filled(config('services.anthropic.api_key')),
            'keySet' => filled(config('services.anthropic.api_key')),
            'model' => config('services.anthropic.model'),
            'chart' => [
                'labels' => $costSeries['labels'],
                'unit' => '',
                'series' => [['name' => 'AI cost (USD)', 'data' => $costSeries['values']], ['name' => 'API calls', 'data' => $callSeries['values']]],
            ],
            'byGym' => $byGym,
            'recent' => AiUsageLog::with('gym:id,name', 'user:id,name')->latest('id')->take(15)->get(),
        ]);
    }
}
