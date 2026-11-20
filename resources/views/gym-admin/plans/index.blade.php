<x-app-layout :title="$kind === 'diet' ? 'Diet Plans' : 'Workout Plans'">
    <x-slot name="actions">
        <a href="{{ route('gym-admin.plans.create', $kind) }}" class="btn-primary">New {{ $kind }} plan</a>
    </x-slot>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex gap-2" aria-label="Plan type">
            @foreach (['workout' => 'Workout plans', 'diet' => 'Diet plans'] as $k => $l)
                <a href="{{ route('gym-admin.plans.index', ['kind' => $k]) }}" class="badge px-3 py-1.5 text-sm {{ $kind === $k ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $l }}</a>
            @endforeach
        </nav>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="kind" value="{{ $kind }}">
            <select name="member" class="input w-auto py-2" onchange="this.form.submit()" aria-label="Filter by member"><option value="">All members</option>@foreach ($allMembers as $m)<option value="{{ $m->id }}" {{ (string) request('member') === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach</select>
            <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" name="archived" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500" {{ request()->boolean('archived') ? 'checked' : '' }} onchange="this.form.submit()"> Include archived</label>
        </form>
    </div>

    <div class="card mb-6 p-5" data-intro>
        @php $pct = $coverage['total'] ? round($coverage['with'] / $coverage['total'] * 100) : 0; @endphp
        <div class="flex justify-between text-sm"><span class="text-slate-300">Members with an active {{ $kind }} plan</span><span class="text-white">{{ $coverage['with'] }} / {{ $coverage['total'] }}</span></div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full bg-volt-400" style="width: {{ $pct }}%"></div></div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
        @forelse ($plans as $plan)
            <article class="card flex flex-col p-5 {{ $plan->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold text-white">{{ $plan->title }}</h2>
                        <p class="text-sm text-slate-400">for {{ optional($members->get($plan->member_id))->name }}</p>
                    </div>
                    <span class="badge {{ $plan->is_active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ $plan->is_active ? 'Active' : 'Archived' }}</span>
                </div>
                <p class="mt-3 flex-1 text-sm text-slate-400">
                    @if ($kind === 'workout')
                        {{ count($plan->days ?? []) }} training days · {{ collect($plan->days)->sum(function ($d) { return count($d['exercises'] ?? []); }) }} exercises
                        @if ($plan->goal)<br>Goal: {{ $plan->goal }}@endif
                    @else
                        {{ count($plan->meals ?? []) }} meals @if ($plan->daily_calories)· {{ number_format($plan->daily_calories) }} kcal/day @endif
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-ink-700 pt-4 text-sm">
                    <span class="text-xs text-slate-500">by {{ optional($plan->trainer)->name ?: '—' }} · {{ $plan->created_at->format('d M') }}</span>
                    <div class="flex gap-3">
                        <a href="{{ route('gym-admin.plans.create', [$kind, 'from' => $plan->id]) }}" class="text-slate-300 hover:text-white">Copy</a>
                        <a href="{{ route('gym-admin.plans.edit', [$kind, $plan->id]) }}" class="text-volt-400 hover:text-volt-300">Edit</a>
                        <form method="POST" action="{{ route('gym-admin.plans.archive', [$kind, $plan->id]) }}">@csrf<button class="text-slate-400 hover:text-white">{{ $plan->is_active ? 'Archive' : 'Restore' }}</button></form>
                    </div>
                </div>
            </article>
        @empty
            <div class="card p-8 text-center md:col-span-2 xl:col-span-3">
                <p class="text-slate-400">No {{ $kind }} plans yet.</p>
                <a href="{{ route('gym-admin.plans.create', $kind) }}" class="btn-primary mt-4">Create one</a>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $plans->links() }}</div>
</x-app-layout>
