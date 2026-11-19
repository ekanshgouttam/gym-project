<x-app-layout title="Weight Tracking">
    @php
        $current = $stats->currentWeight();
        $change = $stats->weightChange();
        $target = optional($profile)->target_weight_kg;
    @endphp
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-5" data-intro><p class="text-sm text-slate-400">Current</p><p class="mt-2 font-display text-3xl font-bold">{{ $current ? number_format($current, 1) : '—' }}<span class="text-base text-slate-500"> kg</span></p></div>
        <div class="card p-5" data-intro><p class="text-sm text-slate-400">Starting</p><p class="mt-2 font-display text-3xl font-bold">{{ optional($profile)->starting_weight_kg ?? '—' }}<span class="text-base text-slate-500"> kg</span></p></div>
        <div class="card p-5" data-intro><p class="text-sm text-slate-400">Change</p><p class="mt-2 font-display text-3xl font-bold">{{ $change !== null ? ($change > 0 ? '+' : '') . $change : '—' }}<span class="text-base text-slate-500"> kg</span></p></div>
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">Target</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ $target ?? '—' }}<span class="text-base text-slate-500"> kg</span></p>
            @if ($target && $current)<p class="mt-1 text-xs text-slate-500">{{ abs(round($current - $target, 1)) }} kg to go</p>@else<a href="{{ route('member.profile') }}" class="mt-1 block text-xs text-volt-400">Set a target</a>@endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card p-5 xl:col-span-2" data-intro>
            <h2 class="text-base font-semibold">Weight over time</h2>
            <div class="mt-2" data-chart="area" data-height="300" data-series='@json($series)'></div>
        </section>

        <form method="POST" action="{{ route('member.weight.store') }}" class="card space-y-5 p-6" data-intro novalidate>
            @csrf
            <h2 class="text-base font-semibold">Log weight</h2>
            <div>
                <label for="logged_on" class="label">Date</label>
                <input id="logged_on" name="logged_on" type="date" max="{{ today()->toDateString() }}" value="{{ old('logged_on', today()->toDateString()) }}" class="input" required>
                @error('logged_on') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="weight_kg" class="label">Weight (kg)</label>
                <input id="weight_kg" name="weight_kg" type="number" step="0.1" inputmode="decimal" value="{{ old('weight_kg') }}" class="input" required>
                @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="note" class="label">Note <span class="text-slate-500">(optional)</span></label>
                <input id="note" name="note" value="{{ old('note') }}" maxlength="120" class="input" placeholder="e.g. morning, after workout">
            </div>
            <button class="btn-primary w-full">Save</button>
            <p class="text-xs text-slate-500">Logging again on the same date replaces that day's entry.</p>
        </form>
    </div>

    <section class="card mt-6 overflow-hidden">
        <h2 class="p-5 text-base font-semibold">Log</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Date</th><th class="table-head">Weight</th><th class="table-head">Change</th><th class="table-head">Note</th><th class="table-head"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($logs as $i => $log)
                        @php $prev = $logs[$i + 1] ?? null; $d = $prev ? round($log->weight_kg - $prev->weight_kg, 1) : null; @endphp
                        <tr>
                            <td class="table-cell whitespace-nowrap text-slate-300">{{ $log->logged_on->format('d M Y') }}</td>
                            <td class="table-cell font-medium text-white">{{ number_format($log->weight_kg, 1) }} kg</td>
                            <td class="table-cell {{ $d === null ? 'text-slate-600' : ($d <= 0 ? 'text-emerald-300' : 'text-amber-300') }}">{{ $d === null ? '—' : ($d > 0 ? '+' : '') . $d }}</td>
                            <td class="table-cell text-slate-400">{{ $log->note }}</td>
                            <td class="table-cell text-right">
                                <form method="POST" action="{{ route('member.weight.destroy', $log) }}" data-confirm="Delete this entry?">@csrf @method('DELETE')<button class="text-xs text-slate-500 hover:text-rose-300">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
