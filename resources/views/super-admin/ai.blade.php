<x-app-layout title="AI usage & costs">
    <x-slot name="actions">
        <a href="{{ route('super-admin.settings') }}" class="btn-ghost">AI settings</a>
    </x-slot>

    @if (! $enabled)
        <div class="mb-6 rounded-xl border border-ember-500/30 bg-ember-500/10 px-4 py-3 text-sm text-ember-300" role="status">
            AI body scans are off: {{ $keySet ? 'switched off in Platform settings.' : 'no ANTHROPIC_API_KEY is set in .env.' }} Members' photos are still saved and can be analysed later.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Cost this month" :value="$stats['cost']" prefix="$" :decimals="2" :hint="'≈ ₹' . number_format($stats['cost'] * $rate) . ' · estimated from token usage'" />
        <x-stat-card label="API calls this month" :value="$stats['calls']" :hint="$stats['failed'] . ' failed or declined'" />
        <x-stat-card label="Tokens this month" :value="$stats['tokens']" hint="input + output + cache" />
        <x-stat-card label="Average cost per report" :value="$stats['avgCost'] ?? 0" prefix="$" :decimals="3" :hint="'model: ' . $model" />
    </div>

    @if ($stats['budget'] > 0)
        <section class="card mt-6 p-5" data-intro>
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-white">Monthly AI budget</span>
                <span class="text-slate-400">${{ number_format($stats['cost'], 2) }} of ${{ number_format($stats['budget'], 2) }}</span>
            </div>
            <div class="mt-3 h-3 overflow-hidden rounded-full bg-ink-700">
                <div class="h-full rounded-full transition-all duration-700 {{ $stats['budgetUsed'] >= 90 ? 'bg-rose-500' : ($stats['budgetUsed'] >= 70 ? 'bg-ember-500' : 'bg-volt-400') }}" style="width: {{ max(1, $stats['budgetUsed']) }}%"></div>
            </div>
            @if ($stats['budgetUsed'] >= 90)<p class="mt-2 text-xs text-rose-300">Over 90% of this month's budget is used.</p>@endif
        </section>
    @endif

    <section class="card mt-6 p-5" data-intro>
        <h2 class="text-base font-semibold">Last 6 months</h2>
        <div class="mt-4" data-chart="line" data-height="280" data-series='@json($chart)'></div>
    </section>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">This month by gym</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Gym</th><th class="table-head">Scan reports</th><th class="table-head">Comparisons</th><th class="table-head">Plan allowance</th><th class="table-head">Failed</th><th class="table-head">Cost</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($byGym as $row)
                        @php $allowance = optional(optional(optional($row->gym)->subscription)->plan)->ai_scans_per_month; @endphp
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell font-medium text-white">{{ optional($row->gym)->name ?: 'No gym' }}</td>
                            <td class="table-cell text-slate-300">{{ (int) $row->scans }}</td>
                            <td class="table-cell text-slate-300">{{ (int) $row->comparisons }}</td>
                            <td class="table-cell {{ $allowance !== null && $row->scans > $allowance ? 'text-ember-400' : 'text-slate-400' }}">{{ $allowance !== null ? number_format($allowance) . ' / month' : '—' }}</td>
                            <td class="table-cell text-slate-400">{{ (int) $row->failed }}</td>
                            <td class="table-cell text-white">${{ number_format($row->cost, 2) }} <span class="text-xs text-slate-500">≈ ₹{{ number_format($row->cost * $rate) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No AI calls this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">Recent calls</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">When</th><th class="table-head">Type</th><th class="table-head">Gym · member</th><th class="table-head">Model</th><th class="table-head">Tokens in / out</th><th class="table-head">Cost</th><th class="table-head">Result</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($recent as $log)
                        <tr class="hover:bg-ink-850/40">
                            <td class="table-cell text-slate-400">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="table-cell text-slate-300">{{ \App\Models\AiUsageLog::KINDS[$log->kind] ?? $log->kind }}</td>
                            <td class="table-cell text-slate-400">{{ optional($log->gym)->name ?: '—' }} · {{ optional($log->user)->name ?: '—' }}</td>
                            <td class="table-cell font-mono text-xs text-slate-400">{{ $log->model }}</td>
                            <td class="table-cell text-slate-400">{{ number_format($log->input_tokens + $log->cache_write_tokens + $log->cache_read_tokens) }} / {{ number_format($log->output_tokens) }}</td>
                            <td class="table-cell text-white">${{ number_format($log->cost_usd, 4) }}</td>
                            <td class="table-cell">
                                @if ($log->success)<span class="badge bg-emerald-500/15 text-emerald-300">OK</span>
                                @else<span class="badge bg-rose-500/15 text-rose-300" title="{{ $log->error }}">Failed</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No AI calls yet. Calls appear here once members run body scans.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <p class="mt-4 text-xs text-slate-500">Costs are estimates from the token counts the API returns and the per-token rates in <code>config/services.php</code>. Your Anthropic invoice is the source of truth.</p>
</x-app-layout>
