<x-app-layout :title="'Enquiry · ' . $enquiry->name">
    <x-slot name="actions">
        <a href="tel:{{ $enquiry->phone }}" class="btn-ghost">Call</a>
        <a href="https://wa.me/{{ ltrim($enquiry->phone, '+') }}" target="_blank" rel="noopener" class="btn-ghost">WhatsApp</a>
        @if ($enquiry->isOpen())
            <a href="{{ route('manage.members.create', ['enquiry' => $enquiry->id]) }}" class="btn-primary">Register as member</a>
        @endif
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        <form method="POST" action="{{ route('gym-admin.enquiries.update', $enquiry) }}" class="card space-y-5 p-6" data-intro novalidate>
            @csrf @method('PUT')
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $enquiry->statusBadge() }}">{{ \App\Models\Enquiry::STATUSES[$enquiry->status] }}</span>
                <span class="text-sm text-slate-500">Added {{ $enquiry->created_at->format('d M Y') }} · {{ \App\Models\Enquiry::SOURCES[$enquiry->source] ?? $enquiry->source }}</span>
                @if ($enquiry->member)<a href="{{ route('manage.users.edit', $enquiry->member) }}" class="text-sm text-emerald-300">Joined as {{ $enquiry->member->name }} →</a>@endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="name" class="label">Name</label><input id="name" name="name" value="{{ old('name', $enquiry->name) }}" class="input" required></div>
                <div><label for="phone" class="label">Mobile</label><input id="phone" name="phone" value="{{ old('phone', $enquiry->phone) }}" class="input" required>@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="email" class="label">Email</label><input id="email" name="email" type="email" value="{{ old('email', $enquiry->email) }}" class="input"></div>
                <div><label for="interest" class="label">Interested in</label><input id="interest" name="interest" list="plan-names" value="{{ old('interest', $enquiry->interest) }}" class="input"><datalist id="plan-names">@foreach ($plans as $p)<option value="{{ $p->name }}">@endforeach</datalist></div>
                <div>
                    <label for="source" class="label">Source</label>
                    <select id="source" name="source" class="input">@foreach (\App\Models\Enquiry::SOURCES as $v => $l)<option value="{{ $v }}" {{ old('source', $enquiry->source) === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="assigned_to" class="label">Assigned to</label>
                    <select id="assigned_to" name="assigned_to" class="input"><option value="">Anyone</option>@foreach ($staff as $s)<option value="{{ $s->id }}" {{ (string) old('assigned_to', $enquiry->assigned_to) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\Enquiry::STATUSES as $v => $l)
                            @if ($v !== 'converted' || $enquiry->status === 'converted')<option value="{{ $v }}" {{ old('status', $enquiry->status) === $v ? 'selected' : '' }}>{{ $l }}</option>@endif
                        @endforeach
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div><label for="next_follow_up_on" class="label">Next follow-up</label><input id="next_follow_up_on" name="next_follow_up_on" type="date" value="{{ old('next_follow_up_on', optional($enquiry->next_follow_up_on)->toDateString()) }}" class="input"></div>
                <div class="sm:col-span-2"><label for="lost_reason" class="label">If lost, why? <span class="text-slate-500">(optional)</span></label><input id="lost_reason" name="lost_reason" value="{{ old('lost_reason', $enquiry->lost_reason) }}" class="input" placeholder="e.g. price, joined another gym, moved away"></div>
                <div class="sm:col-span-2"><label for="notes" class="label">Notes</label><textarea id="notes" name="notes" rows="3" class="input">{{ old('notes', $enquiry->notes) }}</textarea></div>
                <div class="sm:col-span-2"><label for="note" class="label">Log a call or visit</label><input id="note" name="note" class="input" placeholder="e.g. Called, coming for a trial on Saturday"></div>
            </div>
            <div class="flex justify-end gap-3"><a href="{{ route('gym-admin.enquiries.index') }}" class="btn-ghost">Back</a><button type="submit" class="btn-primary">Save</button></div>
        </form>

        <aside class="card h-fit p-6" data-intro>
            <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">Activity</h2>
            <ol class="relative mt-4 space-y-4 border-l border-ink-700 pl-5">
                @forelse ($enquiry->activities as $a)
                    <li class="relative">
                        <span class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full bg-volt-400 ring-4 ring-ink-900"></span>
                        <p class="text-sm text-slate-200">{{ $a->note }}</p>
                        <p class="text-xs text-slate-500">{{ optional($a->user)->name ?: 'System' }} · {{ $a->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No activity yet.</li>
                @endforelse
            </ol>
        </aside>
    </div>
</x-app-layout>
