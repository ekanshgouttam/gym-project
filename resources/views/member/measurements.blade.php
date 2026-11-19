<x-app-layout title="Body Measurements">
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-5 xl:col-span-2" data-intro>
            <h2 class="text-base font-semibold">Trends</h2>
            <div class="mt-2" data-chart="line" data-height="300" data-series='@json($series)'></div>
        </section>

        <form method="POST" action="{{ route('member.measurements.store') }}" class="card space-y-4 p-6" data-intro novalidate>
            @csrf
            <h2 class="text-base font-semibold">Add measurements</h2>
            <div>
                <label for="measured_on" class="label">Date</label>
                <input id="measured_on" name="measured_on" type="date" max="{{ today()->toDateString() }}" value="{{ old('measured_on', today()->toDateString()) }}" class="input" required>
                @error('measured_on') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach ($parts as $key => $label)
                    <div>
                        <label for="{{ $key }}_cm" class="label">{{ $label }} (cm)</label>
                        <input id="{{ $key }}_cm" name="{{ $key }}_cm" type="number" step="0.1" inputmode="decimal" value="{{ old($key . '_cm') }}" class="input">
                        @error($key . '_cm') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div class="col-span-2">
                    <label for="body_fat_pct" class="label">Body fat % <span class="text-slate-500">(if measured)</span></label>
                    <input id="body_fat_pct" name="body_fat_pct" type="number" step="0.1" value="{{ old('body_fat_pct') }}" class="input">
                </div>
            </div>
            <button class="btn-primary w-full">Save</button>
            <p class="text-xs text-slate-500">Measure at the widest point (narrowest for waist), relaxed, tape snug but not tight.</p>
        </form>
    </div>

    <section class="card mt-6 overflow-hidden">
        <h2 class="p-5 text-base font-semibold">All entries</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60">
                    <tr>
                        <th class="table-head">Date</th>
                        @foreach ($parts as $label)<th class="table-head">{{ $label }}</th>@endforeach
                        <th class="table-head">Fat %</th>
                        <th class="table-head"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="table-cell whitespace-nowrap text-slate-300">{{ $row->measured_on->format('d M Y') }}</td>
                            @foreach (array_keys($parts) as $key)<td class="table-cell text-white">{{ $row->{$key . '_cm'} ?? '—' }}</td>@endforeach
                            <td class="table-cell text-white">{{ $row->body_fat_pct ?? '—' }}</td>
                            <td class="table-cell text-right">
                                <form method="POST" action="{{ route('member.measurements.destroy', $row) }}" data-confirm="Delete this entry?">@csrf @method('DELETE')<button class="text-xs text-slate-500 hover:text-rose-300">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No measurements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
