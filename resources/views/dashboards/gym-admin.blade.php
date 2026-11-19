<x-app-layout :title="optional($gym)->name ?? 'Gym dashboard'">
    <x-slot name="actions">
        <a href="{{ route('gym-admin.attendance.index') }}" class="btn-ghost">Check-in</a>
        @if ($gym)<a href="{{ route('manage.gyms.qr', $gym) }}" class="btn-ghost hidden sm:inline-flex">QR code</a>@endif
        <a href="{{ route('manage.members.create') }}" class="btn-primary">Add member</a>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" :prefix="$stat['prefix'] ?? null" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden" data-intro>
            <div class="flex items-center justify-between p-5">
                <h2 class="text-base font-semibold">Fees due this week</h2>
                <a href="{{ route('gym-admin.fees.index') }}" class="text-sm text-volt-400 hover:text-volt-300">Plans & Fees</a>
            </div>
            <ul class="divide-y divide-ink-700 border-t border-ink-700">
                @forelse ($expiring as $m)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0"><p class="truncate text-white">{{ $m->user->name }}</p><p class="text-xs text-slate-500">{{ $m->plan->name }} · ends {{ $m->ends_on->isToday() ? 'today' : $m->ends_on->format('D d M') }}</p></div>
                        <a href="{{ route('gym-admin.fees.renew', $m->user) }}" class="btn-ghost shrink-0 px-3 py-1.5 text-xs">Renew</a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">No memberships end in the next 7 days.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden" data-intro>
            <div class="flex items-center justify-between p-5">
                <h2 class="text-base font-semibold">Follow-ups today</h2>
                <a href="{{ route('gym-admin.enquiries.index', ['view' => 'due']) }}" class="text-sm text-volt-400 hover:text-volt-300">Enquiries</a>
            </div>
            <ul class="divide-y divide-ink-700 border-t border-ink-700">
                @forelse ($followUps as $e)
                    <li>
                        <a href="{{ route('gym-admin.enquiries.show', $e) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-ink-850/40">
                            <div class="min-w-0"><p class="truncate text-white">{{ $e->name }}</p><p class="text-xs {{ $e->isOverdue() ? 'text-ember-400' : 'text-slate-500' }}">{{ $e->isOverdue() ? 'Overdue since ' . $e->next_follow_up_on->format('d M') : 'Due today' }} · {{ $e->phone }}</p></div>
                            <span class="badge {{ $e->statusBadge() }}">{{ \App\Models\Enquiry::STATUSES[$e->status] }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">No follow-ups due. <a href="{{ route('gym-admin.enquiries.index') }}" class="text-volt-400">Add an enquiry</a></li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">New sign-ups, last 6 months</h2>
            <div class="mt-4" data-chart="growth" data-series='@json($growth)'></div>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden" data-intro>
        <div class="flex items-center justify-between p-5">
            <h2 class="text-base font-semibold">Recently joined</h2>
            <a href="{{ route('manage.users.index') }}" class="text-sm text-volt-400 hover:text-volt-300">View all</a>
        </div>
        @include('manage.users._table', ['users' => $recentUsers, 'compact' => true])
    </section>
</x-app-layout>
