<x-app-layout title="My Membership">
    @if ($paymentsEnabled)
        <x-slot name="actions"><script src="https://checkout.razorpay.com/v1/checkout.js" defer></script></x-slot>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Current --}}
        <section class="card relative overflow-hidden p-6 sm:p-8 xl:col-span-2" data-intro>
            <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full {{ $active ? 'bg-volt-400/15' : 'bg-amber-400/10' }} blur-3xl"></div>
            <div class="relative">
                <p class="text-sm text-slate-400">Current membership</p>
                @if ($active)
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-bold">{{ $active->plan->name }}</h2>
                        <span class="badge bg-emerald-500/15 text-emerald-300">Active</span>
                    </div>
                    <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Started</dt><dd class="mt-1 font-medium">{{ $active->starts_on->format('d M Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Expires</dt><dd class="mt-1 font-medium">{{ $active->ends_on->format('d M Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Days left</dt><dd class="mt-1 font-display text-2xl font-bold {{ $active->daysLeft() <= 7 ? 'text-amber-300' : 'text-volt-400' }}">{{ $active->daysLeft() }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Activated by</dt><dd class="mt-1 font-medium">{{ $active->activated_via === 'payment' ? 'Online payment' : 'Front desk' }}</dd></div>
                    </dl>
                    <div class="mt-6 h-2 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full bg-volt-400" style="width: {{ $active->progressPercent() }}%"></div></div>
                @else
                    <h2 class="mt-2 text-3xl font-bold">No active membership</h2>
                    <p class="mt-2 text-slate-400">Choose a plan below. Online payment activates it instantly.</p>
                @endif
            </div>
        </section>

        {{-- Pending / pay --}}
        <section class="card p-6" data-intro>
            <h2 class="text-base font-semibold">{{ $pending ? 'Awaiting activation' : 'Renew or upgrade' }}</h2>
            @if ($pending)
                <p class="mt-4 font-display text-2xl font-bold">{{ $pending->plan->name }}</p>
                <p class="text-sm text-slate-400">₹{{ number_format((float) $pending->amount) }} · {{ $pending->plan->durationLabel() }}</p>
                @if ($active)<p class="mt-2 text-xs text-slate-500">Starts after your current plan ends on {{ $active->ends_on->format('d M Y') }}.</p>@endif
                @if ($paymentsEnabled)
                    <button type="button" class="btn-primary mt-5 w-full" data-pay="{{ route('member.membership.checkout', $pending) }}" data-verify="{{ route('member.payments.verify') }}">Pay ₹{{ number_format((float) $pending->amount) }} securely</button>
                    <p class="mt-2 text-xs text-slate-500">UPI, cards, net banking and wallets via Razorpay.</p>
                @endif
                <p data-pay-error class="mt-2 text-sm text-rose-400" aria-live="polite"></p>
                <p class="mt-4 rounded-xl border border-ink-700 bg-ink-850 p-3 text-xs text-slate-400">Prefer to pay at the gym? Show your Member ID at the front desk and staff will approve your membership.</p>
            @else
                <p class="mt-2 text-sm text-slate-400">Pick a plan below to {{ $active ? 'renew early. It starts when your current plan ends.' : 'get started.' }}</p>
            @endif
        </section>
    </div>

    {{-- Plans --}}
    <section class="mt-8">
        <h2 class="text-lg font-semibold">Available plans</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($plans as $plan)
                <x-plan-card :plan="$plan">
                    <x-slot name="cta">
                        <form method="POST" action="{{ route('member.membership.store') }}" class="mt-8">
                            @csrf
                            <input type="hidden" name="membership_plan_id" value="{{ $plan->id }}">
                            <button class="{{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} w-full" {{ optional($pending)->membership_plan_id === $plan->id ? 'disabled' : '' }}>
                                {{ optional($pending)->membership_plan_id === $plan->id ? 'Selected' : 'Select plan' }}
                            </button>
                        </form>
                    </x-slot>
                </x-plan-card>
            @endforeach
        </div>
    </section>

    {{-- History --}}
    <section class="card mt-8 overflow-hidden">
        <h2 class="p-5 text-base font-semibold">History</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Plan</th><th class="table-head">Period</th><th class="table-head">Amount</th><th class="table-head">Paid via</th><th class="table-head">Status</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($history as $m)
                        <tr>
                            <td class="table-cell font-medium text-white">{{ $m->plan->name }}</td>
                            <td class="table-cell whitespace-nowrap text-slate-400">{{ $m->starts_on ? $m->starts_on->format('d M Y') . ' – ' . $m->ends_on->format('d M Y') : '—' }}</td>
                            <td class="table-cell text-slate-400">₹{{ number_format((float) $m->amount) }}</td>
                            <td class="table-cell text-slate-400">{{ $m->activated_via === 'payment' ? 'Online' : ($m->activated_via === 'admin' ? 'Front desk' : '—') }}</td>
                            <td class="table-cell"><span class="badge {{ $m->isActive() ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $m->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No past memberships.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
