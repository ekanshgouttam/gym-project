<x-app-layout :title="'Body scans · ' . $member->name">
    @php $e = $eligibility; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">
            <div class="flex flex-wrap items-center gap-4">
                @if ($member->avatarUrl())<img src="{{ $member->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover">@endif
                <div>
                    <h2 class="text-xl font-bold">{{ $member->name }}</h2>
                    <p class="text-sm text-slate-400">{{ optional($member->memberProfile)->member_code }} · {{ optional($member->gym)->name }}</p>
                </div>
            </div>
            <dl class="mt-5 grid gap-4 sm:grid-cols-3 text-sm">
                <div><dt class="text-slate-500">Accepted scans</dt><dd class="mt-1 font-display text-2xl font-bold">{{ $scans->whereNull('voided_at')->count() }}</dd></div>
                <div><dt class="text-slate-500">Upload status</dt><dd class="mt-1 font-semibold {{ $e['eligible'] ? 'text-volt-400' : 'text-slate-200' }}">{{ $e['eligible'] ? ($e['override'] ? 'Open (early-upload override)' : 'Open') : 'Locked' }}</dd></div>
                <div><dt class="text-slate-500">Next eligible</dt><dd class="mt-1 font-semibold">{{ $e['next_at'] ? $e['next_at']->format('d M Y, g:i a') : 'Now' }}</dd></div>
            </dl>
        </section>

        <form method="POST" action="{{ route('manage.scans.override', $member) }}" class="card space-y-4 p-6" data-confirm="Allow {{ $member->name }} to upload one scan before the 30-day window ends? This is recorded in the audit log.">
            @csrf
            <h2 class="text-base font-semibold">Allow early upload</h2>
            <p class="text-sm text-slate-400">One-time exception to the 30-day rule, e.g. after a voided scan or a documented mistake.</p>
            <div class="grid grid-cols-3 gap-3">
                <div><label for="days" class="label">Valid for</label><select id="days" name="days" class="input">@foreach ([1, 3, 7, 14] as $d)<option value="{{ $d }}" {{ $d === 7 ? 'selected' : '' }}>{{ $d }} d</option>@endforeach</select></div>
                <div class="col-span-2"><label for="override-reason" class="label">Reason (required)</label><input id="override-reason" name="reason" class="input" minlength="5" required></div>
            </div>
            @error('reason') <p class="field-error">{{ $message }}</p> @enderror
            <button class="btn-ghost w-full">Grant early upload</button>
            @foreach ($overrides as $o)
                <p class="text-xs text-slate-500">{{ $o->created_at->format('d M') }} · {{ optional($o->grantedBy)->name }} · {{ $o->used_at ? 'used ' . $o->used_at->format('d M') : ($o->expires_at->isPast() ? 'expired' : 'open until ' . $o->expires_at->format('d M')) }}</p>
            @endforeach
        </form>
    </div>

    <section class="mt-6 space-y-4">
        <h2 class="text-lg font-semibold">Scans</h2>
        @forelse ($scans as $scan)
            <article class="card p-5 {{ $scan->isVoided() ? 'opacity-70' : '' }}">
                <div class="flex flex-col gap-5 lg:flex-row">
                    <div class="flex gap-2">
                        @foreach (\App\Models\BodyScan::VIEWS as $view)
                            @if ($scan->photoPath($view))
                                <a href="{{ route('manage.scans.original', [$scan, $view]) }}" title="Download untouched original ({{ $view }})" class="group relative block">
                                    <img src="{{ route('media.scan', [$scan, $view]) }}" alt="{{ ucfirst($view) }}" class="h-28 w-20 rounded-lg object-cover" loading="lazy">
                                    <span class="absolute inset-x-0 bottom-0 rounded-b-lg bg-ink-950/80 py-0.5 text-center text-[10px] text-slate-300 opacity-0 transition group-hover:opacity-100">Original ↓</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-white">{{ $scan->created_at->format('d M Y, g:i a') }}
                            @if ($scan->is_baseline)<span class="badge ml-1 bg-volt-400/15 text-volt-300">Baseline</span>@endif
                            @if ($scan->isVoided())<span class="badge ml-1 bg-slate-500/15 text-slate-300">Voided</span>@endif
                        </p>
                        <p class="text-sm text-slate-400">{{ $scan->weight_kg }} kg · {{ $scan->height_cm }} cm · analysis {{ $scan->status }}{{ $scan->status === 'completed' ? ' (score ' . $scan->physique_score . ')' : '' }}</p>
                        @if ($scan->isVoided())<p class="mt-1 text-sm text-slate-400">Voided by {{ optional($scan->voidedBy)->name }}: “{{ $scan->void_reason }}”</p>@endif
                        @if ($scan->error && $scan->status === 'failed')<p class="mt-1 text-sm text-amber-300">{{ $scan->error }}</p>@endif
                        @php $warnings = collect($scan->quality ?? [])->flatMap(function ($q) { return $q['warnings'] ?? []; }); @endphp
                        @if ($warnings->isNotEmpty())<p class="mt-1 text-xs text-slate-500">Photo checks: {{ $warnings->implode(' ') }}</p>@endif
                    </div>
                </div>

                <details class="mt-4 rounded-xl border border-ink-700 bg-ink-850/60 p-4">
                    <summary class="cursor-pointer text-sm font-medium text-slate-200">Corrections</summary>
                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        <form method="POST" action="{{ route('manage.scans.correct', $scan) }}" class="space-y-3">
                            @csrf
                            <p class="text-sm font-medium text-white">Correct recorded values</p>
                            <div class="grid grid-cols-2 gap-2">
                                <input name="weight_kg" type="number" step="0.1" value="{{ $scan->weight_kg }}" class="input" aria-label="Weight (kg)">
                                <input name="height_cm" type="number" step="0.1" value="{{ $scan->height_cm }}" class="input" aria-label="Height (cm)">
                            </div>
                            <input name="reason" placeholder="Reason (required)" class="input" minlength="5" required aria-label="Reason">
                            <button class="btn-ghost w-full">Save correction</button>
                        </form>
                        @if ($scan->isVoided())
                            <form method="POST" action="{{ route('manage.scans.restore', $scan) }}" class="space-y-3" data-confirm="Restore this scan? It will count toward the 30-day rule again.">
                                @csrf
                                <p class="text-sm font-medium text-white">Restore scan</p>
                                <input name="reason" placeholder="Reason (required)" class="input" minlength="5" required aria-label="Reason">
                                <button class="btn-ghost w-full">Restore</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('manage.scans.void', $scan) }}" class="space-y-3" data-confirm="Void this scan? It stays in history but no longer counts toward the 30-day rule or comparisons.">
                                @csrf
                                <p class="text-sm font-medium text-white">Void scan</p>
                                <input name="reason" placeholder="e.g. wrong person in photos" class="input" minlength="5" required aria-label="Reason">
                                <button class="btn-danger w-full">Void</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('manage.scans.retry', $scan) }}" class="space-y-3">
                            @csrf
                            <p class="text-sm font-medium text-white">Re-run AI analysis</p>
                            <input name="reason" placeholder="Reason (required)" class="input" minlength="5" required aria-label="Reason">
                            <button class="btn-ghost w-full" {{ $scan->isVoided() ? 'disabled' : '' }}>Re-run</button>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <x-empty-state title="No scans yet" body="The member's baseline is captured at registration or on their first visit to the AI Body Scanner." />
        @endforelse
    </section>

    <section class="card mt-6 overflow-hidden">
        <h2 class="p-5 text-base font-semibold">Audit log <span class="text-xs font-normal text-slate-500">(append-only)</span></h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">When</th><th class="table-head">Action</th><th class="table-head">By</th><th class="table-head">Scan</th><th class="table-head">Reason / details</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @foreach ($audit as $log)
                        <tr>
                            <td class="table-cell whitespace-nowrap text-slate-400">{{ $log->created_at->format('d M Y, g:i a') }}</td>
                            <td class="table-cell text-white">{{ $log->actionLabel() }}</td>
                            <td class="table-cell text-slate-300">{{ optional($log->actor)->name ?? 'System' }}</td>
                            <td class="table-cell text-slate-400">{{ $log->scan ? $log->scan->created_at->format('d M Y') : '—' }}</td>
                            <td class="table-cell text-slate-400">
                                {{ $log->reason }}
                                @foreach ($log->changes ?? [] as $field => $change)
                                    @if (is_array($change) && array_key_exists('from', $change))<span class="block text-xs">{{ $field }}: {{ $change['from'] }} → {{ $change['to'] }}</span>@endif
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $audit->links() }}</div>
    </section>
</x-app-layout>
