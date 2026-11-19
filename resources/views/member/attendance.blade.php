<x-app-layout title="Attendance">
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-stat-card label="Visits this month" :value="$visits->count()" :hint="$month->format('F Y')" />
        <x-stat-card label="Week streak" :value="$streak" hint="consecutive weeks" />
        <x-stat-card label="Avg. session" :value="$avgMinutes ?? 0" hint="minutes" />
        <x-stat-card label="Total visits" :value="$totalVisits" hint="all time" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        {{-- Calendar --}}
        <section class="card p-6 xl:col-span-2" data-intro>
            <div class="flex items-center justify-between">
                <a href="{{ route('member.attendance', ['month' => $prev->format('Y-m')]) }}" class="btn-ghost px-2.5 py-1.5" aria-label="Previous month">‹</a>
                <h2 class="text-base font-semibold">{{ $month->format('F Y') }}</h2>
                @if ($next)
                    <a href="{{ route('member.attendance', ['month' => $next->format('Y-m')]) }}" class="btn-ghost px-2.5 py-1.5" aria-label="Next month">›</a>
                @else
                    <span class="w-9"></span>
                @endif
            </div>
            <div class="mt-5 grid grid-cols-7 gap-1.5 text-center text-xs">
                @foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $d)<div class="pb-1 text-slate-500">{{ $d }}</div>@endforeach
                @for ($i = 1; $i < $month->dayOfWeekIso; $i++)<div></div>@endfor
                @for ($day = 1; $day <= $month->daysInMonth; $day++)
                    @php
                        $date = $month->copy()->day($day);
                        $visited = isset($visitDays[$day]);
                    @endphp
                    <div class="grid aspect-square place-items-center rounded-lg text-sm {{ $visited ? 'bg-volt-400 font-semibold text-ink-950' : ($date->isToday() ? 'border border-volt-400/50 text-white' : ($date->isFuture() ? 'text-slate-700' : 'bg-ink-850 text-slate-400')) }}" @if($visited) title="Visited" @endif>{{ $day }}</div>
                @endfor
            </div>
            <p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><span class="h-3 w-3 rounded bg-volt-400"></span> Day with a check-in</p>
        </section>

        {{-- Log --}}
        <section class="card overflow-hidden xl:col-span-3" data-intro>
            <h2 class="p-5 text-base font-semibold">Check-ins</h2>
            <ul class="divide-y divide-ink-700 border-t border-ink-700">
                @forelse ($visits as $v)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-white">{{ $v->checked_in_at->format('D, d M') }}</p>
                            <p class="text-xs text-slate-500">{{ optional($v->gym)->name }} · {{ strtoupper($v->method) }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="text-slate-200">{{ $v->checked_in_at->format('g:i a') }}{{ $v->checked_out_at ? ' – ' . $v->checked_out_at->format('g:i a') : '' }}</p>
                            <p class="text-xs text-slate-500">{{ $v->durationMinutes() ? $v->durationMinutes() . ' min' : 'In progress' }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">No check-ins in {{ $month->format('F') }}.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
