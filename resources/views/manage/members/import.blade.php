<x-app-layout title="Import members">
    <x-slot name="actions">
        <a href="{{ route('manage.members.create') }}" class="btn-ghost">Add one member</a>
    </x-slot>

    @if (! $preview)
        <div class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_20rem]">
            <form method="POST" action="{{ route('manage.members.import.preview') }}" enctype="multipart/form-data" class="card space-y-5 p-6" data-intro>
                @csrf
                <h2 class="text-base font-semibold">Upload a CSV file</h2>
                <label for="file" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-ink-600 bg-ink-850 px-6 py-12 text-center transition hover:border-volt-400/60">
                    <svg class="h-10 w-10 text-volt-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>
                    <span class="font-medium text-white">Choose a .csv file</span>
                    <span class="text-xs text-slate-500">Up to {{ \App\Support\MemberCsvImport::MAX_ROWS }} members, 2 MB</span>
                    <input id="file" name="file" type="file" accept=".csv,text/csv" class="mt-2 text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white" required>
                </label>
                @error('file') <p class="field-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-primary w-full">Check file</button>
                <p class="text-center text-xs text-slate-500">Nothing is saved until you review the rows and confirm.</p>
            </form>

            <aside class="card h-fit space-y-4 p-6 text-sm" data-intro>
                <h2 class="text-base font-semibold">File format</h2>
                <a href="{{ route('manage.members.import.template') }}" class="btn-ghost w-full">Download template</a>
                <ul class="space-y-1.5 text-slate-400">
                    <li><span class="font-mono text-slate-200">name</span>, <span class="font-mono text-slate-200">email</span>, <span class="font-mono text-slate-200">phone</span> — required</li>
                    <li><span class="font-mono text-slate-200">date_of_birth</span> — YYYY-MM-DD or DD/MM/YYYY</li>
                    <li><span class="font-mono text-slate-200">gender</span> — male, female or other</li>
                    <li><span class="font-mono text-slate-200">plan</span> — a plan name from your gym</li>
                    <li><span class="font-mono text-slate-200">paid</span> — yes to activate the plan now</li>
                </ul>
                @if ($plans->isNotEmpty())
                    <p class="text-slate-400">Your plans: @foreach ($plans as $p)<span class="text-slate-200">{{ $p->name }}</span>@if (! $loop->last), @endif @endforeach</p>
                @endif
                <p class="text-xs text-slate-500">Imported members set their password with “Forgot password” on the login page.</p>
            </aside>
        </div>
    @else
        @php
            $valid = $preview->filter(function ($r) { return ! $r['errors']; });
            $invalid = $preview->count() - $valid->count();
        @endphp
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Rows in {{ $fileName }}" :value="$preview->count()" />
            <x-stat-card label="Ready to import" :value="$valid->count()" :hint="$valid->where('data.paid', true)->whereNotNull('data.plan_id')->count() . ' with an active plan'" />
            <x-stat-card label="Rows with problems" :value="$invalid" :hint="$invalid ? 'these will be skipped' : 'all good'" />
        </div>

        <div class="card mt-6 overflow-hidden" data-intro>
            <div class="max-h-[32rem] overflow-auto">
                <table class="min-w-full divide-y divide-ink-700 text-sm">
                    <thead class="sticky top-0 bg-ink-850">
                        <tr><th class="table-head">Line</th><th class="table-head">Name</th><th class="table-head">Email</th><th class="table-head">Mobile</th><th class="table-head">Plan</th><th class="table-head">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach ($preview->sortByDesc(function ($r) { return count($r['errors']); }) as $row)
                            <tr class="{{ $row['errors'] ? 'bg-rose-500/5' : '' }}">
                                <td class="table-cell text-slate-500">{{ $row['line'] }}</td>
                                <td class="table-cell text-white">{{ $row['data']['name'] ?: '—' }}</td>
                                <td class="table-cell text-slate-400">{{ $row['data']['email'] ?: '—' }}</td>
                                <td class="table-cell text-slate-400">{{ $row['data']['phone'] ?? '—' }}</td>
                                <td class="table-cell text-slate-400">{{ $row['data']['plan_name'] ?? '—' }}@if (! empty($row['data']['plan_id'])) <span class="text-xs {{ $row['data']['paid'] ? 'text-emerald-300' : 'text-slate-500' }}">· {{ $row['data']['paid'] ? 'paid' : 'unpaid' }}</span>@endif</td>
                                <td class="table-cell">
                                    @if ($row['errors'])
                                        <ul class="space-y-0.5 text-xs text-rose-300">@foreach ($row['errors'] as $e)<li>{{ $e }}</li>@endforeach</ul>
                                    @else
                                        <span class="badge bg-emerald-500/15 text-emerald-300">Ready</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('manage.members.import.run') }}" class="mt-6 flex flex-wrap items-center justify-between gap-4">
            @csrf
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="notify" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" checked>
                Send each member a welcome notification with their Member ID
            </label>
            <div class="flex gap-3">
                <a href="{{ route('manage.members.import') }}" class="btn-ghost">Upload a different file</a>
                <button type="submit" class="btn-primary" {{ $valid->isEmpty() ? 'disabled' : '' }}>Import {{ $valid->count() }} {{ \Illuminate\Support\Str::plural('member', $valid->count()) }}</button>
            </div>
        </form>
    @endif
</x-app-layout>
