<x-app-layout title="Payments">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total paid" :value="$payments->sum('amount_paise') / 100" prefix="₹" />
        <x-stat-card label="Payments" :value="$payments->count()" />
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">Membership</p>
            @if ($active)
                <p class="mt-2 font-display text-2xl font-bold text-white">{{ $active->plan->name }}</p>
                <p class="mt-1 text-xs text-slate-500">active until {{ $active->ends_on->format('d M Y') }} · {{ $active->daysLeft() }} days left</p>
            @else
                <p class="mt-2 font-display text-2xl font-bold text-ember-400">Not active</p>
                <a href="{{ route('member.membership') }}" class="mt-1 inline-block text-xs text-volt-400">Choose a plan →</a>
            @endif
        </div>
    </div>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">Payment history</h2>
        <ul class="divide-y divide-ink-700 border-t border-ink-700">
            @forelse ($payments as $p)
                <li class="flex items-center justify-between gap-3 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white">{{ optional(optional($p->membership)->plan)->name ?: 'Membership' }}</p>
                        <p class="text-xs text-slate-500">{{ $p->paid_at->format('d M Y, H:i') }} · {{ $p->methodLabel() }}</p>
                    </div>
                    <span class="font-display text-lg font-bold text-white">₹{{ number_format($p->amount()) }}</span>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">No payments yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">Memberships</h2>
        <ul class="divide-y divide-ink-700 border-t border-ink-700">
            @forelse ($memberships as $m)
                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <div><p class="text-white">{{ $m->plan->name }}</p><p class="text-xs text-slate-500">{{ $m->starts_on ? $m->starts_on->format('d M Y') . ' – ' . $m->ends_on->format('d M Y') : 'requested ' . $m->created_at->format('d M Y') }}</p></div>
                    <span class="badge {{ ['active' => 'bg-emerald-500/15 text-emerald-300', 'pending' => 'bg-volt-400/15 text-volt-300', 'expired' => 'bg-slate-500/15 text-slate-400'][$m->status] ?? 'bg-rose-500/15 text-rose-300' }}">{{ ucfirst($m->status) }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-500">No memberships yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
