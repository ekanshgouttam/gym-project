<x-app-layout title="Gym subscriptions">
    <x-slot name="actions">
        <a href="{{ route('super-admin.plans.create') }}" class="btn-ghost">New plan</a>
        <a href="{{ route('super-admin.payments.create') }}" class="btn-primary">Record payment</a>
    </x-slot>

    {{-- SaaS plans --}}
    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($plans as $plan)
            <div class="card p-5" data-intro data-tilt>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-display text-lg font-bold text-white">{{ $plan->name }}</p>
                        <p class="mt-1 text-sm text-slate-400"><span class="font-display text-2xl font-bold text-volt-400">₹{{ number_format($plan->price_monthly) }}</span>/month</p>
                        @if ($plan->price_yearly)<p class="text-xs text-slate-500">₹{{ number_format($plan->price_yearly) }}/year</p>@endif
                    </div>
                    <span class="badge {{ $plan->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $plan->is_active ? 'On sale' : 'Hidden' }}</span>
                </div>
                <ul class="mt-4 space-y-1 text-sm text-slate-400">
                    <li>{{ $plan->member_limit ? number_format($plan->member_limit) . ' members' : 'Unlimited members' }}</li>
                    <li>{{ $plan->ai_scans_per_month !== null ? number_format($plan->ai_scans_per_month) . ' AI scans / month' : 'Unlimited AI scans' }}</li>
                    @foreach ($plan->features ?? [] as $feature)<li>{{ $feature }}</li>@endforeach
                </ul>
                <div class="mt-4 flex items-center justify-between border-t border-ink-700 pt-4">
                    <span class="text-sm text-slate-400">{{ $plan->subscriptions_count }} {{ \Illuminate\Support\Str::plural('gym', $plan->subscriptions_count) }}</span>
                    <a href="{{ route('super-admin.plans.edit', $plan) }}" class="text-sm text-volt-400 hover:text-volt-300">Edit plan</a>
                </div>
            </div>
        @endforeach
    </div>

    @if ($unsubscribed->isNotEmpty())
        <div class="mt-6 rounded-xl border border-ember-500/30 bg-ember-500/10 px-4 py-3 text-sm text-ember-300" role="status">
            {{ $unsubscribed->count() }} {{ \Illuminate\Support\Str::plural('gym', $unsubscribed->count()) }} without a subscription:
            @foreach ($unsubscribed as $g)
                <a href="{{ route('super-admin.subscriptions.create', ['gym' => $g->id]) }}" class="font-semibold underline hover:text-white">{{ $g->name }}</a>@if (! $loop->last), @endif
            @endforeach
        </div>
    @endif

    <nav class="mb-4 mt-8 flex flex-wrap gap-2" aria-label="Filter subscriptions">
        <a href="{{ route('super-admin.subscriptions.index') }}" class="badge px-3 py-1.5 text-sm {{ ! $status ? 'bg-volt-400 text-ink-950' : 'bg-ink-800 text-slate-300 hover:text-white' }}">All <span class="ml-1 opacity-70">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\GymSubscription::STATUSES as $key => $label)
            <a href="{{ route('super-admin.subscriptions.index', ['status' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $status === $key ? 'bg-volt-400 text-ink-950' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60">
                    <tr>
                        <th class="table-head">Gym</th>
                        <th class="table-head">Plan</th>
                        <th class="table-head">Amount</th>
                        <th class="table-head">Period</th>
                        <th class="table-head">Status</th>
                        <th class="table-head"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($subscriptions as $s)
                        @php $overdue = $s->ends_on && $s->ends_on->isPast() && in_array($s->status, ['trial', 'active'], true); @endphp
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell font-medium text-white">{{ $s->gym->name }}</td>
                            <td class="table-cell text-slate-300">{{ $s->plan->name }} <span class="text-xs text-slate-500">· {{ $s->billing_cycle }}</span></td>
                            <td class="table-cell text-slate-300">₹{{ number_format($s->amount) }}</td>
                            <td class="table-cell text-slate-400">
                                {{ $s->starts_on->format('d M Y') }} → {{ $s->ends_on ? $s->ends_on->format('d M Y') : 'open' }}
                                @if ($overdue)<p class="text-xs text-ember-400">Ended {{ $s->ends_on->diffForHumans() }}</p>@endif
                            </td>
                            <td class="table-cell"><span class="badge {{ $s->badgeClass() }}">{{ $s->statusLabel() }}</span></td>
                            <td class="table-cell text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('super-admin.payments.create', ['gym' => $s->gym_id]) }}" class="btn-ghost px-3 py-1.5 text-xs">Add payment</a>
                                    <a href="{{ route('super-admin.subscriptions.edit', $s) }}" class="btn-ghost px-3 py-1.5 text-xs">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No subscriptions here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $subscriptions->links() }}</div>
</x-app-layout>
