<x-app-layout title="Attendance">
    <div class="grid gap-6 xl:grid-cols-[24rem_1fr]">
        <div class="space-y-6">
            <section class="card p-5" data-intro>
                <h2 class="text-base font-semibold">Check a member in</h2>
                <form method="GET" class="mt-4 flex gap-2" role="search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, mobile or Member ID" class="input" autofocus aria-label="Find member">
                    <button class="btn-primary shrink-0">Find</button>
                </form>
                @if (request()->filled('q'))
                    <ul class="mt-4 divide-y divide-ink-700">
                        @forelse ($results as $m)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-white">{{ $m->name }}</p>
                                    <p class="text-xs {{ $m->active_membership ? 'text-slate-500' : 'text-ember-400' }}">{{ optional($m->memberProfile)->member_code }} · {{ $m->active_membership ? 'active until ' . $m->active_membership->ends_on->format('d M') : 'no active membership' }}</p>
                                </div>
                                <form method="POST" action="{{ route('gym-admin.attendance.check-in', $m) }}">@csrf<button class="btn-primary px-3 py-1.5 text-xs">Check in</button></form>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-slate-500">No member found.</li>
                        @endforelse
                    </ul>
                @endif
            </section>

            <div class="grid grid-cols-2 gap-4">
                <x-stat-card label="Visits {{ $date->isToday() ? 'today' : $date->format('d M') }}" :value="$visits->count()" />
                <x-stat-card label="Inside now" :value="$inside" />
            </div>

            <section class="card p-5" data-intro>
                <h2 class="text-base font-semibold">Busiest hours (30 days)</h2>
                @php $max = max(1, $byHour->max() ?: 1); @endphp
                <div class="mt-4 flex h-32 items-end gap-1" aria-label="Check-ins by hour">
                    @for ($h = 5; $h <= 23; $h++)
                        @php $n = (int) ($byHour[$h] ?? 0); @endphp
                        <div class="flex flex-1 flex-col items-center gap-1" title="{{ $h }}:00 — {{ $n }} check-ins">
                            <span data-bar="{{ max(3, round($n / $max * 100)) }}" class="w-full rounded-t bg-volt-400/80" style="height: {{ max(3, round($n / $max * 100)) }}%"></span>
                        </div>
                    @endfor
                </div>
                <div class="mt-1 flex justify-between text-[10px] text-slate-500"><span>5am</span><span>12pm</span><span>6pm</span><span>11pm</span></div>
            </section>
        </div>

        <section class="card overflow-hidden" data-intro>
            <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                <h2 class="text-base font-semibold">{{ $date->isToday() ? "Today's" : $date->format('D d M') }} visits</h2>
                <form method="GET"><input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="input py-1.5" onchange="this.form.submit()" aria-label="Choose a day"></form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-ink-700">
                    <thead class="bg-ink-850/60"><tr><th class="table-head">Member</th><th class="table-head">In</th><th class="table-head">Out</th><th class="table-head">How</th><th class="table-head"><span class="sr-only">Action</span></th></tr></thead>
                    <tbody class="divide-y divide-ink-700">
                        @forelse ($visits as $v)
                            <tr class="hover:bg-ink-850/40">
                                <td class="table-cell"><span class="font-medium text-white">{{ optional($v->user)->name }}</span> <span class="text-xs text-slate-500">{{ optional(optional($v->user)->memberProfile)->member_code }}</span></td>
                                <td class="table-cell text-slate-300">{{ $v->checked_in_at->format('H:i') }}</td>
                                <td class="table-cell text-slate-400">{{ $v->checked_out_at ? $v->checked_out_at->format('H:i') . ' (' . $v->durationMinutes() . ' min)' : '—' }}</td>
                                <td class="table-cell text-slate-400">{{ $v->method === 'desk' ? 'Front desk' : strtoupper($v->method) }}</td>
                                <td class="table-cell text-right">
                                    @if (! $v->checked_out_at && $date->isToday())
                                        <form method="POST" action="{{ route('gym-admin.attendance.check-out', $v) }}">@csrf<button class="btn-ghost px-3 py-1.5 text-xs">Check out</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No visits recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
