<x-app-layout :title="'Support · ' . $ticket->subject">
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="card p-6" data-intro>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportRequest::STATUSES[$ticket->status] }}</span>
                <span class="badge {{ $ticket->priorityBadge() }}">{{ \App\Models\SupportRequest::PRIORITIES[$ticket->priority] }}</span>
                <span class="badge bg-ink-800 text-slate-300">{{ \App\Models\SupportRequest::CATEGORIES[$ticket->category] ?? $ticket->category }}</span>
            </div>
            <h2 class="mt-4 text-xl font-semibold">{{ $ticket->subject }}</h2>
            <p class="mt-1 text-sm text-slate-500">From {{ optional($ticket->user)->name ?: 'unknown' }} · {{ optional($ticket->gym)->name ?: 'no gym' }} · {{ $ticket->created_at->format('d M Y, H:i') }}</p>
            <div class="mt-5 whitespace-pre-line rounded-xl border border-ink-700 bg-ink-850 p-4 text-sm text-slate-200">{{ $ticket->message }}</div>

            <form method="POST" action="{{ route('super-admin.support.update', $ticket) }}" class="mt-6 space-y-5">
                @csrf @method('PUT')
                <div>
                    <label for="admin_reply" class="label">Reply to the gym</label>
                    <textarea id="admin_reply" name="admin_reply" rows="6" class="input" placeholder="The gym owner sees this reply in their FlexFit support page.">{{ old('admin_reply', $ticket->admin_reply) }}</textarea>
                    @error('admin_reply') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($ticket->replied_at)<p class="mt-1 text-xs text-slate-500">Last replied {{ $ticket->replied_at->diffForHumans() }}</p>@endif
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="status" class="label">Status</label>
                        <select id="status" name="status" class="input">
                            @foreach (\App\Models\SupportRequest::STATUSES as $v => $l)<option value="{{ $v }}" {{ old('status', $ticket->status) === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="label">Priority</label>
                        <select id="priority" name="priority" class="input">
                            @foreach (\App\Models\SupportRequest::PRIORITIES as $v => $l)<option value="{{ $v }}" {{ old('priority', $ticket->priority) === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('super-admin.support.index') }}" class="btn-ghost">Back</a>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </section>

        <aside class="card h-fit p-6" data-intro>
            <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-500">Gym</h3>
            @if ($ticket->gym)
                <p class="mt-3 font-medium text-white">{{ $ticket->gym->name }}</p>
                <p class="text-sm text-slate-400">{{ collect([$ticket->gym->address, $ticket->gym->city])->filter()->implode(', ') }}</p>
                <p class="mt-2 text-sm text-slate-400">{{ $ticket->gym->phone }}</p>
                @php $sub = $ticket->gym->subscription; @endphp
                @if ($sub)<p class="mt-3"><span class="badge {{ $sub->badgeClass() }}">{{ $sub->plan->name }} · {{ $sub->statusLabel() }}</span></p>@endif
            @else
                <p class="mt-3 text-sm text-slate-500">Not linked to a gym.</p>
            @endif
            @if ($ticket->user)
                <h3 class="mt-6 text-sm font-semibold uppercase tracking-widest text-slate-500">Contact</h3>
                <p class="mt-3 text-sm text-white">{{ $ticket->user->name }}</p>
                <a href="mailto:{{ $ticket->user->email }}" class="text-sm text-volt-400 hover:text-volt-300">{{ $ticket->user->email }}</a>
                <p class="text-sm text-slate-400">{{ $ticket->user->phone }}</p>
            @endif
        </aside>
    </div>
</x-app-layout>
