<x-app-layout title="Membership approvals">
    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Filter by status">
        @foreach (['pending' => 'Pending', 'active' => 'Active', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $value => $label)
            <a href="{{ route('manage.memberships.index', ['status' => $value]) }}" class="rounded-full px-4 py-1.5 text-sm {{ $status === $value ? 'bg-volt-400 text-on-volt font-semibold' : 'border border-ink-600 text-slate-300 hover:border-slate-400' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="space-y-3">
        @forelse ($memberships as $m)
            <article class="card flex flex-col gap-4 p-5 lg:flex-row lg:items-center">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    @if ($m->user->avatarUrl())
                        <img src="{{ $m->user->avatarUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-full object-cover">
                    @else
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-ink-700 text-sm font-semibold text-white">{{ $m->user->initials() }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-white">{{ $m->user->name }} <span class="text-xs font-normal text-slate-500">{{ optional($m->user->memberProfile)->member_code }}</span></p>
                        <p class="truncate text-sm text-slate-400">{{ $m->user->phone }} · {{ $m->user->email }}</p>
                        @if (auth()->user()->isSuperAdmin())<p class="text-xs text-slate-500">{{ $m->gym->name }}</p>@endif
                    </div>
                </div>
                <div class="text-sm lg:w-56">
                    <p class="font-medium text-white">{{ $m->plan->name }} · ₹{{ number_format((float) $m->amount) }}</p>
                    <p class="text-slate-500">
                        @if ($m->status === 'active') {{ $m->starts_on->format('d M Y') }} → {{ $m->ends_on->format('d M Y') }} ({{ $m->activated_via }})
                        @else Requested {{ $m->created_at->diffForHumans() }} @endif
                    </p>
                    @if ($m->payments->where('status', 'created')->isNotEmpty())<p class="text-xs text-amber-300">Online payment started, not completed</p>@endif
                    @if ($m->notes)<p class="text-xs text-slate-500">“{{ $m->notes }}”</p>@endif
                </div>
                @if ($m->status === 'pending')
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('manage.memberships.approve', $m) }}" data-confirm="Activate {{ $m->user->name }}'s {{ $m->plan->name }} membership? Confirm payment has been collected.">
                            @csrf
                            <div class="flex gap-2">
                                <select name="method" class="input w-auto py-1.5 text-xs" aria-label="How was it paid?">
                                    @foreach (\App\Models\Payment::DESK_METHODS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                                </select>
                                <button class="btn-primary px-3 py-2 text-xs">Paid · approve</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('manage.memberships.reject', $m) }}" class="flex gap-2" data-confirm="Reject this request?">
                            @csrf
                            <label class="sr-only" for="reason-{{ $m->id }}">Reason</label>
                            <input id="reason-{{ $m->id }}" name="reason" placeholder="Reason (optional)" class="input w-40 py-2 text-xs">
                            <button class="btn-danger px-3 py-2 text-xs">Reject</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="card p-10 text-center text-slate-500">No {{ $status }} memberships.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $memberships->links() }}</div>
</x-app-layout>
