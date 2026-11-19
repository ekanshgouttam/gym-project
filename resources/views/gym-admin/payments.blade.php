<x-app-layout title="Payments">
    <x-slot name="actions">
        <a href="{{ route('gym-admin.payments.export', request()->only('month', 'method')) }}" class="btn-ghost">Export CSV</a>
        <a href="{{ route('gym-admin.fees.index') }}" class="btn-primary">Collect a fee</a>
    </x-slot>

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div><label for="month" class="label">Month</label><input id="month" type="month" name="month" value="{{ $month }}" max="{{ now()->format('Y-m') }}" class="input py-2" onchange="this.form.submit()"></div>
        <div>
            <label for="method" class="label">Method</label>
            <select id="method" name="method" class="input py-2" onchange="this.form.submit()"><option value="">All methods</option>@foreach (\App\Models\Payment::METHODS as $v => $l)<option value="{{ $v }}" {{ request('method') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total collected" :value="$total" prefix="₹" :hint="\Illuminate\Support\Carbon::parse($month . '-01')->format('F Y')" />
        <x-stat-card label="Payments" :value="$count" />
        @foreach ($byMethod->take(2) as $row)
            <x-stat-card :label="\App\Models\Payment::METHODS[$row->gateway] ?? $row->gateway" :value="$row->paise / 100" prefix="₹" :hint="$row->n . ' payments'" />
        @endforeach
    </div>

    <div class="card mt-6 overflow-hidden" data-intro>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Date</th><th class="table-head">Member</th><th class="table-head">Plan</th><th class="table-head">Amount</th><th class="table-head">Method</th><th class="table-head">Recorded by</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($payments as $p)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell text-slate-400">{{ $p->paid_at->format('d M, H:i') }}</td>
                            <td class="table-cell font-medium text-white">{{ optional($p->user)->name }}</td>
                            <td class="table-cell text-slate-400">{{ optional(optional($p->membership)->plan)->name }}</td>
                            <td class="table-cell text-white">₹{{ number_format($p->amount()) }}</td>
                            <td class="table-cell text-slate-400">{{ $p->methodLabel() }}@if ($p->note)<p class="text-xs text-slate-500">{{ $p->note }}</p>@endif</td>
                            <td class="table-cell text-slate-400">{{ optional($p->recorder)->name ?: ($p->gateway === 'razorpay' ? 'Paid online' : '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No payments this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-app-layout>
