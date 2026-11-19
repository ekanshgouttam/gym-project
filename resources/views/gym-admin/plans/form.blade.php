<x-app-layout :title="($plan->exists ? 'Edit ' : 'New ') . $kind . ' plan'">
    @php
        $action = $plan->exists ? route('gym-admin.plans.update', [$kind, $plan->id]) : route('gym-admin.plans.store', $kind);
        $existingDays = collect(old('days') ? [] : ($plan->days ?? []));
        $existingMeals = collect(old('meals') ? [] : ($plan->meals ?? []));
    @endphp

    @if ($errors->any())
        <div class="mb-6 max-w-5xl rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" role="alert">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <form method="POST" action="{{ $action }}" class="grid max-w-6xl gap-6 lg:grid-cols-[1fr_20rem]" novalidate>
        @csrf
        @if ($plan->exists) @method('PUT') @endif

        <div class="space-y-6">
            <section class="card space-y-4 p-6" data-intro>
                <div><label for="title" class="label">Plan title</label><input id="title" name="title" value="{{ old('title', $plan->title) }}" class="input" placeholder="{{ $kind === 'workout' ? 'e.g. 4-day upper/lower split' : 'e.g. Fat loss — 1,900 kcal' }}" required></div>
                @if ($kind === 'workout')
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><label for="goal" class="label">Goal</label><input id="goal" name="goal" value="{{ old('goal', $plan->goal) }}" class="input" placeholder="Strength"></div>
                        <div><label for="starts_on" class="label">Starts</label><input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', optional($plan->starts_on)->toDateString() ?? today()->toDateString()) }}" class="input"></div>
                        <div><label for="ends_on" class="label">Ends</label><input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', optional($plan->ends_on)->toDateString()) }}" class="input"></div>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div><label for="daily_calories" class="label">kcal / day</label><input id="daily_calories" name="daily_calories" type="number" value="{{ old('daily_calories', $plan->daily_calories) }}" class="input"></div>
                        @foreach (['protein' => 'Protein (g)', 'carbs' => 'Carbs (g)', 'fat' => 'Fat (g)'] as $k => $l)
                            <div><label for="{{ $k }}" class="label">{{ $l }}</label><input id="{{ $k }}" name="{{ $k }}" type="number" value="{{ old($k, $plan->macros[$k] ?? '') }}" class="input"></div>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($kind === 'workout')
                <section class="card space-y-4 p-6" data-intro>
                    <div>
                        <h2 class="text-base font-semibold">Training days</h2>
                        <p class="text-sm text-slate-400">One exercise per line: <span class="font-mono text-xs text-slate-300">Name | sets | reps | rest</span> — e.g. <span class="font-mono text-xs text-slate-300">Bench press | 4 | 8-10 | 90s</span>. Leave a day empty for rest.</p>
                    </div>
                    @foreach ($days as $i => $dayName)
                        @php
                            $existing = $existingDays->firstWhere('day', $dayName);
                            $lines = $existing ? collect($existing['exercises'] ?? [])->map(function ($e) { return rtrim(implode(' | ', [$e['name'], $e['sets'] ?? '', $e['reps'] ?? '', $e['rest'] ?? '']), ' |'); })->implode("\n") : '';
                        @endphp
                        <details class="rounded-xl border border-ink-700 bg-ink-850" {{ ($existing || old("days.$i.exercises")) ? 'open' : '' }}>
                            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-white">{{ $dayName }} <span class="text-xs font-normal text-slate-500">{{ $existing ? ($existing['focus'] ?: count($existing['exercises'] ?? []) . ' exercises') : 'rest / tap to add' }}</span></summary>
                            <div class="space-y-3 border-t border-ink-700 p-4">
                                <input type="hidden" name="days[{{ $i }}][day]" value="{{ $dayName }}">
                                <input name="days[{{ $i }}][focus]" value="{{ old("days.$i.focus", $existing['focus'] ?? '') }}" class="input" placeholder="Focus, e.g. Chest & triceps" aria-label="{{ $dayName }} focus">
                                <textarea name="days[{{ $i }}][exercises]" rows="5" class="input font-mono text-sm" placeholder="Bench press | 4 | 8-10 | 90s&#10;Incline dumbbell press | 3 | 10-12 | 60s" aria-label="{{ $dayName }} exercises">{{ old("days.$i.exercises", $lines) }}</textarea>
                            </div>
                        </details>
                    @endforeach
                </section>
            @else
                <section class="card space-y-4 p-6" data-intro>
                    <div>
                        <h2 class="text-base font-semibold">Meals</h2>
                        <p class="text-sm text-slate-400">One food per line. Leave unused meals empty.</p>
                    </div>
                    @php $defaults = [['07:30', 'Breakfast'], ['11:00', 'Mid-morning'], ['13:30', 'Lunch'], ['17:00', 'Pre-workout'], ['20:30', 'Dinner'], ['', '']]; @endphp
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($defaults as $i => [$t, $n])
                            @php $meal = $existingMeals->get($i); @endphp
                            <div class="space-y-2 rounded-xl border border-ink-700 bg-ink-850 p-4">
                                <div class="grid grid-cols-[5.5rem_1fr] gap-2">
                                    <input name="meals[{{ $i }}][time]" value="{{ old("meals.$i.time", $meal['time'] ?? ($plan->exists ? '' : $t)) }}" class="input" placeholder="Time" aria-label="Meal {{ $i + 1 }} time">
                                    <input name="meals[{{ $i }}][name]" value="{{ old("meals.$i.name", $meal['name'] ?? ($plan->exists ? '' : $n)) }}" class="input" placeholder="Meal name" aria-label="Meal {{ $i + 1 }} name">
                                </div>
                                <textarea name="meals[{{ $i }}][items]" rows="4" class="input text-sm" placeholder="3 egg whites + 1 whole egg&#10;1 cup oats with milk" aria-label="Meal {{ $i + 1 }} foods">{{ old("meals.$i.items", implode("\n", $meal['items'] ?? [])) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="card p-6" data-intro>
                <label for="notes" class="label">Notes for the member</label>
                <textarea id="notes" name="notes" rows="3" class="input" placeholder="{{ $kind === 'workout' ? 'Warm up 10 minutes. Increase weight when all sets hit the top of the rep range.' : 'Drink 3–4 litres of water. Swap rice for millets twice a week.' }}">{{ old('notes', $plan->notes) }}</textarea>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5" data-intro>
                @if ($plan->exists)
                    <h2 class="text-base font-semibold">Member</h2>
                    <p class="mt-2 text-sm text-white">{{ optional($member)->name }}</p>
                @else
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-base font-semibold">Assign to</h2>
                        <input type="search" placeholder="Filter" class="input w-28 py-1 text-xs" oninput="const q=this.value.toLowerCase();document.querySelectorAll('[data-member-row]').forEach(r=>r.hidden=!r.dataset.memberRow.includes(q))" aria-label="Filter members">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Each member gets their own copy. Their current {{ $kind }} plan is archived.</p>
                    <ul class="mt-3 max-h-80 space-y-1 overflow-y-auto">
                        @forelse ($members as $m)
                            <li data-member-row="{{ strtolower($m->name) }}">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-200 hover:bg-ink-850">
                                    <input type="checkbox" name="member_ids[]" value="{{ $m->id }}" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ in_array($m->id, old('member_ids', $selected)) ? 'checked' : '' }}>
                                    {{ $m->name }}
                                    @if (optional($m->memberProfile)->trainer_id === auth()->id())<span class="badge bg-volt-400/15 text-volt-300">yours</span>@endif
                                </label>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">No active members.</li>
                        @endforelse
                    </ul>
                @endif
            </section>
            <button type="submit" class="btn-primary w-full">{{ $plan->exists ? 'Save plan' : 'Assign plan' }}</button>
            <a href="{{ route('gym-admin.plans.index', ['kind' => $kind]) }}" class="btn-ghost w-full">Cancel</a>
        </aside>
    </form>
</x-app-layout>
