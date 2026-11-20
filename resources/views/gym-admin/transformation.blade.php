<x-app-layout title="Body Transformation">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Scans this month" :value="$thisMonth" />
        <x-stat-card label="Due for a scan" :value="$counts['due'] ?? 0" hint="30 days since their last scan" />
        <x-stat-card label="No baseline yet" :value="$counts['no_baseline'] ?? 0" hint="ask them to take their first scan" />
        <x-stat-card label="Avg AI visual score" :value="$avgScore ? round($avgScore) : 0" :hint="$avgScore ? 'last 90 days, out of 100' : 'no reports yet'" />
    </div>

    <nav class="mb-4 mt-8 flex flex-wrap gap-2" aria-label="Filter members">
        @foreach (['all' => 'All members', 'due' => 'Due for scan', 'waiting' => 'Up to date', 'no_baseline' => 'No baseline'] as $key => $label)
            <a href="{{ route('gym-admin.transformation', ['filter' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $filter === $key ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }}</span></a>
        @endforeach
    </nav>

    <div class="card overflow-hidden" data-intro>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Member</th><th class="table-head">Scans</th><th class="table-head">Last scan</th><th class="table-head">AI visual score</th><th class="table-head">Next scan</th><th class="table-head"><span class="sr-only">Open</span></th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell"><span class="font-medium text-white">{{ $r['member']->name }}</span> <span class="text-xs text-slate-500">{{ optional($r['member']->memberProfile)->member_code }}</span>@if (optional(optional($r['member']->memberProfile)->trainer)->name)<p class="text-xs text-slate-500">Trainer: {{ $r['member']->memberProfile->trainer->name }}</p>@endif</td>
                            <td class="table-cell text-slate-300">{{ $r['member']->scans_count }}</td>
                            <td class="table-cell text-slate-400">{{ $r['last'] ? $r['last']->created_at->format('d M Y') : '—' }}@if ($r['last'] && $r['last']->is_baseline) <span class="badge ml-1 bg-ink-800 text-slate-300">baseline</span>@endif</td>
                            <td class="table-cell text-slate-300">{{ $r['last'] && $r['last']->status === 'completed' ? $r['last']->physique_score . '/100' : ($r['last'] ? ucfirst($r['last']->status) : '—') }}</td>
                            <td class="table-cell">
                                @if ($r['state'] === 'no_baseline')<span class="badge bg-ember-500/15 text-ember-300">Needs baseline</span>
                                @elseif ($r['state'] === 'due')<span class="badge bg-volt-400/15 text-volt-300">Due now</span>
                                @else<span class="text-sm text-slate-400">{{ $r['next']->format('d M') }} <span class="text-xs text-slate-500">({{ today()->diffInDays($r['next']) }} days)</span></span>@endif
                            </td>
                            <td class="table-cell text-right"><a href="{{ route('manage.scans.index', $r['member']) }}" class="btn-ghost px-3 py-1.5 text-xs">Scans</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No members here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs text-slate-500">AI visual scores describe how photos look; they are not body-fat, muscle or medical measurements.</p>
</x-app-layout>
