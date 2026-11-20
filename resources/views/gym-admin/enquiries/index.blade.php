<x-app-layout title="Enquiries">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Enquiries, last 90 days" :value="$stats['total90']" />
        <x-stat-card label="Joined as members" :value="$stats['converted90']" hint="last 90 days" />
        <x-stat-card label="Conversion rate" :value="$stats['rate']" hint="% of enquiries that joined" />
        <x-stat-card label="Follow-ups due" :value="$due" :hint="$stats['avgDays'] ? 'avg ' . $stats['avgDays'] . ' days to convert' : 'today or overdue'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <nav class="flex flex-wrap gap-2" aria-label="Filter enquiries">
                    @php $open = ($counts['new'] ?? 0) + ($counts['follow_up'] ?? 0) + ($counts['trial'] ?? 0); @endphp
                    @foreach (['open' => ['Open', $open], 'due' => ['Due', $due], 'converted' => ['Joined', $counts['converted'] ?? 0], 'lost' => ['Lost', $counts['lost'] ?? 0], 'all' => ['All', $counts->sum()]] as $key => [$label, $n])
                        <a href="{{ route('gym-admin.enquiries.index', ['view' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $view === $key ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $n }}</span></a>
                    @endforeach
                </nav>
                <form method="GET" class="flex gap-2"><input type="hidden" name="view" value="{{ $view }}"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or phone" class="input w-52 py-2" aria-label="Search enquiries"></form>
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-700">
                        <thead class="bg-ink-850/60"><tr><th class="table-head">Name</th><th class="table-head">Source</th><th class="table-head">Interested in</th><th class="table-head">Next follow-up</th><th class="table-head">Status</th></tr></thead>
                        <tbody class="divide-y divide-ink-700">
                            @forelse ($enquiries as $e)
                                <tr class="cursor-pointer hover:bg-ink-850/40" onclick="window.location='{{ route('gym-admin.enquiries.show', $e) }}'">
                                    <td class="table-cell"><a href="{{ route('gym-admin.enquiries.show', $e) }}" class="font-medium text-white hover:text-volt-300">{{ $e->name }}</a><p class="text-xs text-slate-500">{{ $e->phone }}</p></td>
                                    <td class="table-cell text-slate-400">{{ \App\Models\Enquiry::SOURCES[$e->source] ?? $e->source }}</td>
                                    <td class="table-cell text-slate-400">{{ $e->interest ?: '—' }}</td>
                                    <td class="table-cell {{ $e->isOverdue() ? 'text-ember-400' : 'text-slate-400' }}">{{ $e->next_follow_up_on ? ($e->next_follow_up_on->isToday() ? 'Today' : $e->next_follow_up_on->format('D d M')) : '—' }}</td>
                                    <td class="table-cell"><span class="badge {{ $e->statusBadge() }}">{{ \App\Models\Enquiry::STATUSES[$e->status] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No enquiries here yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $enquiries->links() }}</div>

            @if ($bySource->isNotEmpty())
                <section class="card mt-6 p-5" data-intro>
                    <h2 class="text-base font-semibold">Where enquiries come from (90 days)</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($bySource as $row)
                            @php $rate = $row->total ? round($row->converted / $row->total * 100) : 0; @endphp
                            <li>
                                <div class="flex justify-between text-sm"><span class="text-slate-300">{{ \App\Models\Enquiry::SOURCES[$row->source] ?? $row->source }}</span><span class="text-slate-400">{{ $row->total }} · <span class="text-white">{{ $rate }}% joined</span></span></div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full bg-volt-400" style="width: {{ max(2, $rate) }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <form method="POST" action="{{ route('gym-admin.enquiries.store') }}" class="card h-fit space-y-4 p-5" data-intro novalidate>
            @csrf
            <h2 class="text-base font-semibold">New enquiry</h2>
            <div><label for="name" class="label">Name</label><input id="name" name="name" value="{{ old('name') }}" class="input" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="phone" class="label">Mobile</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="input" required>@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="label">Email <span class="text-slate-500">(optional)</span></label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="source" class="label">Source</label>
                    <select id="source" name="source" class="input">@foreach (\App\Models\Enquiry::SOURCES as $v => $l)<option value="{{ $v }}" {{ old('source') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="next_follow_up_on" class="label">Follow up on</label>
                    <input id="next_follow_up_on" name="next_follow_up_on" type="date" value="{{ old('next_follow_up_on', today()->addDay()->toDateString()) }}" class="input">
                </div>
            </div>
            <div>
                <label for="interest" class="label">Interested in</label>
                <input id="interest" name="interest" list="plan-names" value="{{ old('interest') }}" class="input" placeholder="e.g. Quarterly, personal training">
                <datalist id="plan-names">@foreach ($plans as $p)<option value="{{ $p->name }}">@endforeach</datalist>
            </div>
            <div>
                <label for="assigned_to" class="label">Assigned to</label>
                <select id="assigned_to" name="assigned_to" class="input"><option value="">Me / anyone</option>@foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            </div>
            <div><label for="notes" class="label">Notes</label><textarea id="notes" name="notes" rows="2" class="input">{{ old('notes') }}</textarea></div>
            <button type="submit" class="btn-primary w-full">Save enquiry</button>
        </form>
    </div>
</x-app-layout>
