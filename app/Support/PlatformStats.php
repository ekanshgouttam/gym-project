<?php

namespace App\Support;

use App\Models\AiUsageLog;
use App\Models\GymSubscription;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Platform-wide numbers for the Super Admin panel. All money is INR unless the name says usd. */
class PlatformStats
{
    /** Members whose membership is active today, per gym: [gym_id => count]. */
    public static function activeMembersByGym(): array
    {
        return Membership::query()
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->groupBy('gym_id')
            ->selectRaw('gym_id, COUNT(DISTINCT user_id) as total')
            ->pluck('total', 'gym_id')
            ->map(function ($v) { return (int) $v; })
            ->all();
    }

    public static function activeMembers(): int
    {
        return (int) Membership::query()
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->distinct()
            ->count('user_id');
    }

    /** Monthly recurring revenue from paid (active) subscriptions. */
    public static function mrr(): float
    {
        return (float) GymSubscription::where('status', 'active')->get()->sum(function ($s) { return $s->mrr(); });
    }

    public static function subscriptionRevenue(Carbon $from, ?Carbon $to = null): int
    {
        return (int) SubscriptionPayment::where('status', 'paid')
            ->where('paid_at', '>=', $from)
            ->when($to, function ($q) use ($to) { $q->where('paid_at', '<', $to); })
            ->sum('amount');
    }

    /** Online payments members made to gyms through FlexFit (Razorpay), in INR. */
    public static function memberRevenue(Carbon $from, ?Carbon $to = null): int
    {
        return (int) round(Payment::where('status', 'paid')
            ->where('paid_at', '>=', $from)
            ->when($to, function ($q) use ($to) { $q->where('paid_at', '<', $to); })
            ->sum('amount_paise') / 100);
    }

    public static function aiCostUsd(Carbon $from, ?Carbon $to = null): float
    {
        return (float) AiUsageLog::where('created_at', '>=', $from)
            ->when($to, function ($q) use ($to) { $q->where('created_at', '<', $to); })
            ->sum('cost_usd');
    }

    /**
     * Sum of a column per month for the last $months months, for charts.
     * @return array{labels: string[], values: float[]}
     */
    public static function monthly($query, string $dateColumn, string $sumExpression, int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = (clone $query)
            ->where($dateColumn, '>=', $start)
            ->groupBy('ym')
            ->select(DB::raw("DATE_FORMAT({$dateColumn}, '%Y-%m') as ym"), DB::raw("{$sumExpression} as total"))
            ->pluck('total', 'ym');

        $out = ['labels' => [], 'values' => []];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $out['labels'][] = $m->format('M');
            $out['values'][] = round((float) ($rows[$m->format('Y-m')] ?? 0), 2);
        }

        return $out;
    }
}
