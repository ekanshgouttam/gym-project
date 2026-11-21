<x-app-layout title="Registration requests">
    <p class="mb-4 max-w-3xl text-sm text-slate-400">People who signed up in the FlexFit app and chose your gym. Approving makes them a member with a Member ID; they can then pick a plan. Rejected requests are kept here for your records.</p>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Filter by status">
        @foreach (\App\Http\Controllers\GymAdmin\RegistrationRequestController::TABS as $value => $label)
            <a href="{{ route('gym-admin.registrations.index', ['status' => $value]) }}" class="rounded-full px-4 py-1.5 text-sm {{ $tab === $value ? 'bg-volt-400 text-on-volt font-semibold' : 'border border-ink-600 text-slate-300 hover:border-slate-400' }}">{{ $label }} <span class="opacity-70">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <div class="space-y-3">
        @forelse ($requests as $r)
            <article class="card flex flex-col gap-4 p-5 lg:flex-row lg:items-center">
                <div class="flex min-w-0 items-center gap-4 lg:flex-1">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-ink-700 text-sm font-semibold text-white">{{ $r->user->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-white">{{ $r->user->name }}</p>
                        <p class="truncate text-sm text-slate-400">{{ $r->user->phone }} · {{ $r->user->email }}</p>
                    </div>
                </div>
                <div class="text-sm lg:w-64">
                    <p class="text-slate-300">Requested {{ $r->created_at->format('d M Y, g:i a') }} <span class="text-slate-500">· {{ \App\Models\RegistrationRequest::SOURCES[$r->source] ?? $r->source }}</span></p>
                    @if ($r->decided_at)
                        <p class="text-slate-500">{{ ucfirst($r->status) }} {{ $r->decided_at->format('d M Y') }}{{ $r->decider ? ' by ' . $r->decider->name : '' }}</p>
                    @endif
                    @if ($r->rejection_reason)<p class="text-xs text-slate-500">“{{ $r->rejection_reason }}”</p>@endif
                </div>
                @if ($r->isPending())
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('gym-admin.registrations.approve', $r) }}" data-confirm="Approve {{ $r->user->name }} as a member of your gym?">
                            @csrf
                            <button class="btn-primary px-3 py-2 text-xs">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('gym-admin.registrations.reject', $r) }}" class="flex gap-2" data-confirm="Reject {{ $r->user->name }}'s request?">
                            @csrf
                            <label class="sr-only" for="reason-{{ $r->id }}">Reason</label>
                            <input id="reason-{{ $r->id }}" name="reason" maxlength="200" placeholder="Reason (optional, shown to them)" class="input w-56 py-2 text-xs">
                            <button class="btn-danger px-3 py-2 text-xs">Reject</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="card p-10 text-center text-slate-500">No {{ strtolower(\App\Http\Controllers\GymAdmin\RegistrationRequestController::TABS[$tab]) }} requests.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $requests->links() }}</div>
</x-app-layout>
