<x-app-layout title="Trainers">
    <x-slot name="actions">
        <a href="{{ route('manage.users.create', ['role' => 'trainer']) }}" class="btn-primary">Add trainer</a>
    </x-slot>

    @if ($unassigned)
        <div class="mb-6 rounded-xl border border-ember-500/30 bg-ember-500/10 px-4 py-3 text-sm text-ember-200" role="status">{{ $unassigned }} active {{ \Illuminate\Support\Str::plural('member', $unassigned) }} don't have a trainer yet. Open a trainer to assign members.</div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
        @forelse ($trainers as $t)
            <div class="card p-5" data-tilt>
                <div class="flex items-center gap-4">
                    @if ($t->avatarUrl())<img src="{{ $t->avatarUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover">
                    @else<span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-volt-400 to-ember-500 font-display text-lg font-bold text-on-volt">{{ $t->initials() }}</span>@endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-white">{{ $t->name }}</p>
                        <p class="truncate text-sm text-slate-400">{{ optional($t->trainerProfile)->specialty ?: 'No specialty set' }}</p>
                    </div>
                </div>
                <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-ink-850 p-2"><dt class="text-[11px] text-slate-500">Members</dt><dd class="font-display text-xl font-bold text-white">{{ $assigned[$t->id] ?? 0 }}</dd></div>
                    <div class="rounded-xl bg-ink-850 p-2"><dt class="text-[11px] text-slate-500">Active plans</dt><dd class="font-display text-xl font-bold text-white">{{ $workouts[$t->id] ?? 0 }}</dd></div>
                    <div class="rounded-xl bg-ink-850 p-2"><dt class="text-[11px] text-slate-500">Years</dt><dd class="font-display text-xl font-bold text-white">{{ optional($t->trainerProfile)->experience_years ?? 0 }}</dd></div>
                </dl>
                <div class="mt-4 flex items-center justify-between border-t border-ink-700 pt-4 text-sm">
                    <span class="badge {{ $t->status === 'active' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">{{ ucfirst($t->status) }}</span>
                    <span class="text-xs text-slate-500">{{ optional($t->trainerProfile)->is_public ? 'On public page' : 'Hidden from public page' }}</span>
                    <a href="{{ route('gym-admin.trainers.edit', $t) }}" class="text-volt-400 hover:text-volt-300">Manage</a>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center md:col-span-2 xl:col-span-3">
                <p class="text-slate-400">No trainers yet.</p>
                <a href="{{ route('manage.users.create', ['role' => 'trainer']) }}" class="btn-primary mt-4">Add your first trainer</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
