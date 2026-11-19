<x-app-layout title="Workout Plans">
    @if (! $active)
        <x-empty-state title="No workout plan yet" body="Your trainer will assign a plan built around your goal. You'll get a notification when it's ready." />
    @else
        <section class="card relative overflow-hidden p-6 sm:p-8" data-intro>
            <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-volt-400/10 blur-3xl"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold">{{ $active->title }}</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ $active->goal }}
                        @if ($active->trainer) · by {{ $active->trainer->name }} @endif
                        @if ($active->ends_on) · until {{ $active->ends_on->format('d M Y') }} @endif
                    </p>
                </div>
                <span class="badge bg-emerald-500/15 text-emerald-300">Active</span>
            </div>
            @if ($active->notes)<p class="relative mt-4 max-w-3xl text-sm text-slate-300">{{ $active->notes }}</p>@endif
        </section>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
            @foreach ($active->days as $day)
                @php $isToday = strcasecmp($day['day'] ?? '', $today) === 0; @endphp
                <section class="card p-5 {{ $isToday ? 'border-volt-400/60 shadow-glow' : '' }}">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-widest {{ $isToday ? 'text-volt-400' : 'text-slate-500' }}">{{ $day['day'] ?? '' }}{{ $isToday ? ' · Today' : '' }}</p>
                    </div>
                    <h3 class="mt-1 text-lg font-semibold">{{ $day['focus'] ?? 'Workout' }}</h3>
                    <ul class="mt-4 divide-y divide-ink-700">
                        @foreach ($day['exercises'] ?? [] as $ex)
                            <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span class="text-slate-200">{{ $ex['name'] }}</span>
                                <span class="shrink-0 text-right text-slate-400">{{ $ex['sets'] ?? '' }} × {{ $ex['reps'] ?? '' }}@if(! empty($ex['rest']))<span class="block text-xs text-slate-600">rest {{ $ex['rest'] }}</span>@endif</span>
                            </li>
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
