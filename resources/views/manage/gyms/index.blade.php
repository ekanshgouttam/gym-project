<x-app-layout title="Registered gyms">
    <x-slot name="actions">
        <a href="{{ route('manage.gyms.create') }}" class="btn-primary">New gym</a>
    </x-slot>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Filter gyms">
            @foreach (['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                <a href="{{ route('manage.gyms.index', array_filter(['filter' => $key, 'q' => request('q')])) }}"
                   class="badge px-3 py-1.5 text-sm {{ (string) $filter === $key ? 'bg-volt-400 text-ink-950' : 'bg-ink-800 text-slate-300 hover:text-white' }}">
                    {{ $label }} <span class="ml-1 opacity-70">{{ $counts[$key ?: 'all'] }}</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" class="flex gap-2">
            @if ($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, city, email" class="input w-56 py-2" aria-label="Search gyms">
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60">
                    <tr>
                        <th class="table-head">Gym</th>
                        <th class="table-head">Owner</th>
                        <th class="table-head">Subscription</th>
                        <th class="table-head">Active members</th>
                        <th class="table-head">Status</th>
                        <th class="table-head"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($gyms as $gym)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    @if ($gym->logoUrl())
                                        <img src="{{ $gym->logoUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-lg bg-ink-850 object-contain p-0.5">
                                    @else
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-ink-700 text-xs font-semibold text-white">{{ strtoupper(substr($gym->name, 0, 2)) }}</span>
                                    @endif
                                    <div>
                                        <p class="font-medium text-white">{{ $gym->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $gym->city ?: '—' }} · since {{ $gym->created_at->format('d M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell text-slate-400">
                                @if ($gym->owner)
                                    <p class="text-slate-200">{{ $gym->owner->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $gym->owner->email }}</p>
                                @else
                                    <span class="text-slate-500">Created by FlexFit</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if ($gym->subscription)
                                    <a href="{{ route('super-admin.subscriptions.edit', $gym->subscription) }}" class="badge {{ $gym->subscription->badgeClass() }}">{{ $gym->subscription->plan->name }} · {{ $gym->subscription->statusLabel() }}</a>
                                    @if ($gym->subscription->ends_on)<p class="mt-1 text-xs text-slate-500">until {{ $gym->subscription->ends_on->format('d M Y') }}</p>@endif
                                @else
                                    <a href="{{ route('super-admin.subscriptions.create', ['gym' => $gym->id]) }}" class="text-sm text-volt-400 hover:text-volt-300">Start subscription</a>
                                @endif
                            </td>
                            <td class="table-cell text-slate-300">{{ $activeMembers[$gym->id] ?? 0 }} <span class="text-xs text-slate-500">/ {{ $gym->users_count }} people</span></td>
                            <td class="table-cell"><span class="badge {{ $gym->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $gym->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="table-cell text-right"><div class="flex justify-end gap-2"><a href="{{ route('manage.gyms.qr', $gym) }}" class="btn-ghost px-3 py-1.5 text-xs">QR code</a><a href="{{ route('manage.gyms.edit', $gym) }}" class="btn-ghost px-3 py-1.5 text-xs">Edit</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No gyms match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $gyms->links() }}</div>
</x-app-layout>
