<x-app-layout title="Active members">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Active members" :value="$totals['active']" hint="membership active today, all gyms" />
        <x-stat-card label="Registered members" :value="$totals['registered']" hint="all member accounts" />
        <x-stat-card label="New this month" :value="$totals['new']" :hint="now()->format('F Y')" />
    </div>

    <section class="card mt-6 p-5" data-intro>
        <h2 class="text-base font-semibold">New member sign-ups, last 6 months</h2>
        <div class="mt-4" data-chart="growth" data-series='@json($growth)'></div>
    </section>

    <div class="card mt-6 overflow-hidden" data-intro>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60">
                    <tr>
                        <th class="table-head">Gym</th>
                        <th class="table-head">Active</th>
                        <th class="table-head">Registered</th>
                        <th class="table-head">New this month</th>
                        <th class="table-head">Plan capacity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell font-medium text-white">{{ $row['gym']->name }} <span class="text-xs text-slate-500">{{ $row['gym']->city }}</span></td>
                            <td class="table-cell font-display text-lg text-white">{{ number_format($row['active']) }}</td>
                            <td class="table-cell text-slate-400">{{ number_format($row['registered']) }}</td>
                            <td class="table-cell text-slate-400">{{ $row['new'] ? '+' . $row['new'] : '—' }}</td>
                            <td class="table-cell w-64">
                                @if ($row['limit'])
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full {{ $row['usage'] >= 90 ? 'bg-ember-500' : 'bg-volt-400' }}" style="width: {{ $row['usage'] }}%"></div></div>
                                        <span class="w-24 text-right text-xs text-slate-400">{{ $row['active'] }} / {{ number_format($row['limit']) }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">{{ $row['gym']->subscription ? 'Unlimited' : 'No plan' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No gyms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
