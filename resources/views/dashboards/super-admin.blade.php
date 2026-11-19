<x-app-layout title="Platform overview">
    <x-slot name="actions">
        <a href="{{ route('super-admin.payments.create') }}" class="btn-ghost">Record payment</a>
        <a href="{{ route('manage.gyms.create') }}" class="btn-primary">New gym</a>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" :prefix="$stat['prefix'] ?? null" :decimals="$stat['decimals'] ?? 0" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card p-5" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Subscription revenue, last 6 months</h2>
                <a href="{{ route('super-admin.revenue') }}" class="text-sm text-volt-400 hover:text-volt-300">Revenue report</a>
            </div>
            <div class="mt-4" data-chart="area" data-height="260" data-series='@json($revenue)'></div>
        </section>
        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">New accounts, last 6 months</h2>
            <div class="mt-4" data-chart="growth" data-series='@json($growth)'></div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card p-5" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Newest gyms</h2>
                <a href="{{ route('manage.gyms.index') }}" class="text-sm text-volt-400 hover:text-volt-300">All gyms</a>
            </div>
            <ul class="mt-4 divide-y divide-ink-700">
                @forelse ($gyms as $gym)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($gym->logoUrl())
                                <img src="{{ $gym->logoUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-lg bg-ink-850 object-contain p-0.5">
                            @else
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-ink-700 text-xs font-semibold text-white">{{ strtoupper(substr($gym->name, 0, 2)) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-white">{{ $gym->name }}</p>
                                <p class="text-xs text-slate-500">{{ $gym->city ?: '—' }} · {{ $gym->users_count }} people · joined {{ $gym->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @if ($gym->subscription)
                            <span class="badge {{ $gym->subscription->badgeClass() }}">{{ $gym->subscription->plan->name }} · {{ $gym->subscription->statusLabel() }}</span>
                        @else
                            <span class="badge bg-slate-500/15 text-slate-400">No plan</span>
                        @endif
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">No gyms yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Open support requests</h2>
                <a href="{{ route('super-admin.support.index') }}" class="text-sm text-volt-400 hover:text-volt-300">All requests</a>
            </div>
            <ul class="mt-4 divide-y divide-ink-700">
                @forelse ($tickets as $ticket)
                    <li>
                        <a href="{{ route('super-admin.support.show', $ticket) }}" class="flex items-center justify-between gap-3 py-3 hover:text-white">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-white">{{ $ticket->subject }}</p>
                                <p class="text-xs text-slate-500">{{ optional($ticket->gym)->name ?: 'No gym' }} · {{ $ticket->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="badge {{ $ticket->priorityBadge() }}">{{ \App\Models\SupportRequest::PRIORITIES[$ticket->priority] }}</span>
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Nothing waiting. Nice.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden" data-intro>
        <div class="flex items-center justify-between p-5">
            <h2 class="text-base font-semibold">Newest accounts</h2>
            <a href="{{ route('manage.users.index') }}" class="text-sm text-volt-400 hover:text-volt-300">Manage users</a>
        </div>
        @include('manage.users._table', ['users' => $recentUsers, 'compact' => true])
    </section>
</x-app-layout>
