<x-app-layout title="Diet Plans">
    @if (! $active)
        <x-empty-state title="No diet plan yet" body="Your trainer will set calorie and macro targets with a meal plan for your goal." />
    @else
        @php
            $macros = $active->macros ?? [];
            $kcal = [ 'protein' => ($macros['protein'] ?? 0) * 4, 'carbs' => ($macros['carbs'] ?? 0) * 4, 'fat' => ($macros['fat'] ?? 0) * 9 ];
            $total = max(1, array_sum($kcal));
        @endphp
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card p-6 sm:p-8 xl:col-span-2" data-intro>
                <h2 class="text-2xl font-bold">{{ $active->title }}</h2>
                <p class="mt-1 text-sm text-slate-400">@if ($active->trainer) by {{ $active->trainer->name }} · @endif updated {{ $active->updated_at->format('d M Y') }}</p>
                @if ($active->notes)<p class="mt-4 text-sm text-slate-300">{{ $active->notes }}</p>@endif
            </section>
            <section class="card p-6" data-intro>
                <p class="text-sm text-slate-400">Daily target</p>
                <p class="mt-1 font-display text-4xl font-bold">{{ number_format($active->daily_calories ?? 0) }}<span class="text-base text-slate-500"> kcal</span></p>
                <div class="mt-4 flex h-2 overflow-hidden rounded-full" aria-hidden="true">
                    <span class="bg-volt-400" style="width: {{ $kcal['protein'] / $total * 100 }}%"></span>
                    <span class="bg-sky-400" style="width: {{ $kcal['carbs'] / $total * 100 }}%"></span>
                    <span class="bg-fuchsia-400" style="width: {{ $kcal['fat'] / $total * 100 }}%"></span>
                </div>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                    @foreach (['protein' => ['Protein', 'text-volt-400'], 'carbs' => ['Carbs', 'text-sky-300'], 'fat' => ['Fat', 'text-fuchsia-300']] as $k => [$l, $c])
                        <div><dt class="text-xs text-slate-500">{{ $l }}</dt><dd class="font-semibold {{ $c }}">{{ $macros[$k] ?? 0 }} g</dd></div>
                    @endforeach
                </dl>
            </section>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
            @foreach ($active->meals as $meal)
                <section class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-volt-400">{{ $meal['time'] ?? '' }}</p>
                    <h3 class="mt-1 text-lg font-semibold">{{ $meal['name'] ?? 'Meal' }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        @foreach ($meal['items'] ?? [] as $item)
                            <li class="flex gap-3"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-500"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif

    @if ($past->isNotEmpty())
        <section class="card mt-8 p-6">
            <h2 class="text-base font-semibold">Previous plans</h2>
            <ul class="mt-4 divide-y divide-ink-700 text-sm">
                @foreach ($past as $p)
                    <li class="flex justify-between py-2.5"><span class="text-slate-300">{{ $p->title }}</span><span class="text-slate-500">{{ $p->created_at->format('M Y') }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
