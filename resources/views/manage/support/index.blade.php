<x-app-layout title="FlexFit support">
    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        <section class="card overflow-hidden" data-intro>
            <h2 class="p-5 text-base font-semibold">Your requests</h2>
            <ul class="divide-y divide-ink-700">
                @forelse ($tickets as $t)
                    <li>
                        <a href="{{ route('manage.support.show', $t) }}" class="flex items-center justify-between gap-3 px-5 py-4 hover:bg-ink-850/40">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-white">{{ $t->subject }}</p>
                                <p class="text-xs text-slate-500">{{ \App\Models\SupportRequest::CATEGORIES[$t->category] ?? $t->category }} · {{ $t->created_at->diffForHumans() }}@if ($t->admin_reply) · <span class="text-volt-300">FlexFit replied</span>@endif</p>
                            </div>
                            <span class="badge {{ $t->statusBadge() }}">{{ \App\Models\SupportRequest::STATUSES[$t->status] }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">No requests yet. Ask us anything using the form.</li>
                @endforelse
            </ul>
            <div class="p-5">{{ $tickets->links() }}</div>
        </section>

        <form method="POST" action="{{ route('manage.support.store') }}" class="card h-fit space-y-5 p-6" data-intro novalidate>
            @csrf
            <h2 class="text-base font-semibold">New request</h2>
            <div>
                <label for="subject" class="label">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" class="input" required>
                @error('subject') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="category" class="label">Topic</label>
                    <select id="category" name="category" class="input">
                        @foreach (\App\Models\SupportRequest::CATEGORIES as $v => $l)<option value="{{ $v }}" {{ old('category') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="priority" class="label">Priority</label>
                    <select id="priority" name="priority" class="input">
                        @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $v => $l)<option value="{{ $v }}" {{ old('priority', 'normal') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="message" class="label">How can we help?</label>
                <textarea id="message" name="message" rows="6" class="input" required>{{ old('message') }}</textarea>
                @error('message') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full">Send to FlexFit</button>
            @if ($supportEmail)<p class="text-center text-xs text-slate-500">Or email <a href="mailto:{{ $supportEmail }}" class="text-volt-400">{{ $supportEmail }}</a></p>@endif
        </form>
    </div>
</x-app-layout>
