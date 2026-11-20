<x-app-layout title="Demo requests">
    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter demo requests">
        <a href="{{ route('super-admin.demos.index') }}" class="badge px-3 py-1.5 text-sm {{ ! $status ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">All <span class="ml-1 opacity-70">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\DemoRequest::STATUSES as $key => $label)
            <a href="{{ route('super-admin.demos.index', ['status' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $status === $key ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <div class="space-y-4">
        @forelse ($demos as $d)
            <article class="card p-5" data-intro>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold">{{ $d->gym_name }} <span class="text-sm font-normal text-slate-500">{{ $d->city }}</span></h2>
                        <p class="mt-1 text-sm text-slate-300">{{ $d->name }} · <a href="mailto:{{ $d->email }}" class="text-volt-400 hover:text-volt-300">{{ $d->email }}</a> · <a href="tel:{{ $d->phone }}" class="text-volt-400 hover:text-volt-300">{{ $d->phone }}</a></p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ \App\Models\DemoRequest::SIZES[$d->members] ?? 'Size not given' }}
                            · prefers {{ $d->preferred_date ? $d->preferred_date->format('D d M') : 'any day' }}{{ $d->preferred_time ? ', ' . strtolower(\App\Models\DemoRequest::TIMES[$d->preferred_time]) : '' }}
                            · received {{ $d->created_at->diffForHumans() }}
                        </p>
                        @if ($d->message)<p class="mt-3 whitespace-pre-line text-sm text-slate-400">{{ $d->message }}</p>@endif
                    </div>
                    <form method="POST" action="{{ route('super-admin.demos.update', $d) }}" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <span class="badge {{ $d->statusBadge() }}">{{ \App\Models\DemoRequest::STATUSES[$d->status] }}</span>
                        <select name="status" class="input w-auto py-1.5 text-sm" aria-label="Change status for {{ $d->gym_name }}" onchange="this.form.submit()">
                            @foreach (\App\Models\DemoRequest::STATUSES as $v => $l)<option value="{{ $v }}" {{ $d->status === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                        </select>
                        <noscript><button class="btn-ghost px-3 py-1.5 text-xs">Save</button></noscript>
                    </form>
                </div>
            </article>
        @empty
            <x-empty-state title="No demo requests yet" body="Requests from the Book a Demo page on the website appear here." />
        @endforelse
    </div>
    <div class="mt-6">{{ $demos->links() }}</div>
</x-app-layout>
