<x-app-layout title="Revenue">
    @php
        $change = $stats['lastMonth'] > 0 ? round(($stats['thisMonth'] - $stats['lastMonth']) / $stats['lastMonth'] * 100) : null;
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="MRR" :value="$stats['mrr']" prefix="₹" hint="monthly recurring, paid subscriptions" />
        <x-stat-card label="Subscription revenue this month" :value="$stats['thisMonth']" prefix="₹" :hint="$change === null ? 'no revenue last month' : ($change >= 0 ? '+' : '') . $change . '% vs last month'" />
        <x-stat-card label="Subscription revenue all time" :value="$stats['allTime']" prefix="₹" />
        <x-stat-card label="Member payments via FlexFit this month" :value="$stats['memberThisMonth']" prefix="₹" hint="online payments members made to their gyms (gym income, not FlexFit's)" />
        <x-stat-card label="AI cost this month" :value="$stats['aiCostInr']" prefix="₹" hint="estimated, converted from USD" />
        <x-stat-card label="Gross margin this month" :value="$stats['thisMonth'] - $stats['aiCostInr']" prefix="₹" hint="subscription revenue minus AI cost" />
    </div>

    <section class="card mt-6 p-5" data-intro>
        <h2 class="text-base font-semibold">Last 6 months</h2>
        <div class="mt-4" data-chart="area" data-height="300" data-series='@json($chart)'></div>
    </section>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">Subscription revenue by gym (all time)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Gym</th><th class="table-head">Total paid</th><th class="table-head">Last payment</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($byGym as $row)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell font-medium text-white">{{ optional($row->gym)->name ?: 'Deleted gym' }}</td>
                            <td class="table-cell text-white">₹{{ number_format($row->total) }}</td>
                            <td class="table-cell text-slate-400">{{ \Illuminate\Support\Carbon::parse($row->last_paid)->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No subscription payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
