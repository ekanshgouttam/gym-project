<x-app-layout title="Plans & Fees">
    <x-slot name="actions">
        <a href="{{ route('gym-admin.fees.plans.create') }}" class="btn-primary">New plan</a>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Collected this month" :value="$collectedThisMonth" prefix="₹" />
        <x-stat-card label="Ending in 7 days" :value="$due['expiring']->count()" hint="renewals coming up" />
        <x-stat-card label="Expired, not renewed" :value="$due['expired']->count()" hint="last 60 days" />
        <x-stat-card label="Outstanding" :value="$outstanding" prefix="₹" hint="unpaid requests + expired renewals" />
    </div>

    <nav class="mb-4 mt-8 flex flex-wrap gap-2" aria-label="Sections">
        @foreach (['due' => 'Fees due', 'plans' => 'Membership plans'] as $key => $label)
            <a href="{{ route('gym-admin.fees.index', ['tab' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $tab === $key ? 'bg-volt-400 text-ink-950' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($tab === 'plans')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
            @forelse ($ownPlans as $plan)
                <div class="card p-5 {{ $plan->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-display text-lg font-bold text-white">{{ $plan->name }} @if ($plan->is_featured)<span class="badge ml-1 bg-volt-400/15 text-volt-300">Popular</span>@endif</p>
                            <p class="text-sm text-slate-400">{{ $plan->durationLabel() }}</p>
                        </div>
                        <span class="badge {{ $plan->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $plan->is_active ? 'On sale' : 'Hidden' }}</span>
                    </div>
                    <p class="mt-4 font-display text-3xl font-bold text-white">₹{{ number_format((float) $plan->price) }}</p>
                    @if ($plan->duration_months > 1)<p class="text-xs text-slate-500">≈ ₹{{ number_format($plan->monthlyPrice()) }}/month</p>@endif
                    <div class="mt-4 flex items-center justify-between border-t border-ink-700 pt-4 text-sm">
                        <span class="text-slate-400">{{ $plan->memberships_count }} active</span>
                        <a href="{{ route('gym-admin.fees.plans.edit', $plan) }}" class="text-volt-400 hover:text-volt-300">Edit</a>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center md:col-span-2 xl:col-span-3">
                    <p class="text-slate-400">You haven't created your own plans yet.</p>
                    <a href="{{ route('gym-admin.fees.plans.create') }}" class="btn-primary mt-4">Create your first plan</a>
                </div>
            @endforelse
        </div>
        @if ($sharedPlans->isNotEmpty())
            <p class="mt-6 text-sm text-slate-500">Also offered at your gym (FlexFit default plans): @foreach ($sharedPlans as $p)<span class="text-slate-300">{{ $p->name }} ₹{{ number_format((float) $p->price) }}</span>@if (! $loop->last), @endif @endforeach</p>
        @endif
    @else
        @foreach ([
            'expiring' => ['Ending in the next 7 days', 'Remind them, or collect the renewal now.'],
            'expired' => ['Expired and not renewed', 'Members whose last membership ended in the past 60 days.'],
            'pending' => ['Waiting for payment', 'Requested in the app but not paid yet.'],
        ] as $key => [$title, $hint])
            <section class="card mb-6 overflow-hidden" data-intro>
                <div class="p-5"><h2 class="text-base font-semibold">{{ $title }} <span class="ml-1 text-sm text-slate-500">{{ $due[$key]->count() }}</span></h2><p class="text-sm text-slate-500">{{ $hint }}</p></div>
                <ul class="divide-y divide-ink-700 border-t border-ink-700">
                    @forelse ($due[$key] as $m)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="text-white">{{ $m->user->name }} <span class="text-slate-500">{{ $m->user->phone }}</span></p>
                                <p class="text-xs text-slate-500">
                                    {{ $m->plan->name }} · ₹{{ number_format((float) $m->amount) }}
                                    @if ($key === 'pending') · requested {{ $m->created_at->diffForHumans() }}
                                    @else · {{ $m->ends_on->isPast() ? 'ended' : 'ends' }} {{ $m->ends_on->format('d M Y') }} @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                @if ($m->user->phone)<a href="https://wa.me/{{ ltrim($m->user->phone, '+') }}?text={{ rawurlencode('Hi ' . $m->user->name . ', a reminder from ' . auth()->user()->gym->name . ': your ' . $m->plan->name . ' membership ' . ($key === 'pending' ? 'is waiting for payment.' : ($m->ends_on->isPast() ? 'has ended. Renew to keep training!' : 'ends on ' . $m->ends_on->format('d M') . '.'))) }}" target="_blank" rel="noopener" class="btn-ghost px-3 py-1.5 text-xs">WhatsApp</a>@endif
                                @if ($key === 'pending')
                                    <a href="{{ route('manage.memberships.index') }}" class="btn-primary px-3 py-1.5 text-xs">Approve payment</a>
                                @else
                                    <a href="{{ route('gym-admin.fees.renew', $m->user) }}" class="btn-primary px-3 py-1.5 text-xs">Collect renewal</a>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Nothing here.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
        <p class="text-xs text-slate-500">Members also get automatic reminders in their app before their membership ends (Settings → Reminders).</p>
    @endif
</x-app-layout>
