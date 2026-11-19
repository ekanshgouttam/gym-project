<x-app-layout title="Subscription payments">
    <x-slot name="actions">
        <a href="{{ route('super-admin.payments.create') }}" class="btn-primary">Record payment</a>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Received this month" :value="$paidThisMonth" prefix="₹" />
        <x-stat-card label="Received all time" :value="$paidAllTime" prefix="₹" />
        <x-stat-card label="Pending" :value="$pending" prefix="₹" hint="recorded but not yet received" />
    </div>

    <form method="GET" class="mb-4 mt-8 flex flex-wrap gap-3">
        <select name="gym" class="input w-auto py-2" aria-label="Filter by gym" onchange="this.form.submit()">
            <option value="">All gyms</option>
            @foreach ($gyms as $g)<option value="{{ $g->id }}" {{ (string) request('gym') === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
        </select>
        <select name="status" class="input w-auto py-2" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">Any status</option>
            @foreach (\App\Models\SubscriptionPayment::STATUSES as $v => $l)<option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
        </select>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60">
                    <tr>
                        <th class="table-head">Date</th>
                        <th class="table-head">Gym</th>
                        <th class="table-head">Plan</th>
                        <th class="table-head">Amount</th>
                        <th class="table-head">Method</th>
                        <th class="table-head">Covers</th>
                        <th class="table-head">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($payments as $p)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell text-slate-400">{{ optional($p->paid_at)->format('d M Y') ?: '—' }}</td>
                            <td class="table-cell font-medium text-white">{{ optional($p->gym)->name }}</td>
                            <td class="table-cell text-slate-400">{{ optional(optional($p->subscription)->plan)->name ?: '—' }}</td>
                            <td class="table-cell text-white">₹{{ number_format($p->amount) }}</td>
                            <td class="table-cell text-slate-400">{{ \App\Models\SubscriptionPayment::METHODS[$p->method] ?? $p->method }}@if ($p->reference)<p class="font-mono text-xs text-slate-500">{{ $p->reference }}</p>@endif</td>
                            <td class="table-cell text-slate-400">{{ $p->period_start ? $p->period_start->format('d M') . ' → ' . $p->period_end->format('d M Y') : '—' }}</td>
                            <td class="table-cell"><span class="badge {{ ['paid' => 'bg-emerald-500/15 text-emerald-300', 'pending' => 'bg-volt-400/15 text-volt-300', 'failed' => 'bg-rose-500/15 text-rose-300', 'refunded' => 'bg-slate-500/15 text-slate-400'][$p->status] }}">{{ \App\Models\SubscriptionPayment::STATUSES[$p->status] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-app-layout>
