<x-app-layout :title="($scan->is_baseline ? 'Baseline scan · ' : 'Scan · ') . $scan->created_at->format('d M Y')">
    <x-slot name="actions"><a href="{{ route('member.scanner') }}" class="btn-ghost">All scans</a></x-slot>

    @php
        $a = $scan->analysis ?? [];
        $warnings = collect($scan->quality ?? [])->flatMap(function ($q) { return collect($q['warnings'] ?? [])->map(function ($w) use ($q) { return ucfirst($q['view']) . ': ' . $w; }); });
    @endphp

    @if ($scan->isVoided())
        <p class="mb-6 rounded-xl border border-slate-500/30 bg-slate-500/10 px-4 py-3 text-sm text-slate-300">
            This scan was voided by gym staff on {{ $scan->voided_at->format('d M Y') }}: “{{ $scan->void_reason }}”. It stays in your history but isn't used for comparisons or the 30-day schedule.
        </p>
    @endif

    @if ($scan->status === 'pending')
        <section class="card flex flex-col items-center px-6 py-16 text-center" data-poll="{{ route('member.scanner.status', $scan) }}">
            <div class="relative h-24 w-24">
                <span class="absolute inset-0 animate-ping rounded-full bg-volt-400/30"></span>
                <span class="absolute inset-3 animate-pulse rounded-full bg-volt-400/40"></span>
                <span class="absolute inset-7 rounded-full bg-volt-400"></span>
            </div>
            <h2 class="mt-8 text-2xl font-bold">Analysing your scan…</h2>
            <p class="mt-2 max-w-md text-sm text-slate-400">Your photos are saved. The report usually takes under a minute; this page updates automatically and you'll get a notification.</p>
        </section>
    @elseif ($scan->status === 'failed')
        <x-empty-state title="Your photos are saved, but the AI report isn't ready" :body="$scan->error ?: 'The analysis could not be completed.'">
            @if ($scan->canRetryAnalysis())
                <form method="POST" action="{{ route('member.scanner.retry', $scan) }}" data-busy="Retrying…">@csrf<button class="btn-primary">Retry analysis</button></form>
            @else
                <p class="text-sm text-slate-400">Please ask the front desk to re-run the analysis.</p>
            @endif
        </x-empty-state>
        <p class="mt-3 text-center text-xs text-slate-500">This scan is accepted and counts as your {{ $scan->is_baseline ? 'baseline' : 'monthly scan' }}. Re-running the analysis doesn't need new photos.</p>
    @else
        <p class="mb-6 rounded-xl border border-ink-600 bg-ink-850 px-4 py-3 text-xs text-slate-400">
            AI visual report: observations from photos, not body-fat, muscle-mass or medical measurements. Lighting, distance and pose affect how a body looks on camera.
        </p>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card relative overflow-hidden p-6 sm:p-8 xl:col-span-2" data-intro>
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-volt-400/15 blur-3xl"></div>
                <div class="relative grid gap-6 sm:grid-cols-3">
                    <div>
                        <p class="text-sm text-slate-400">AI visual score</p>
                        <p class="mt-1 font-display text-5xl font-bold text-volt-400"><span data-count="{{ $scan->physique_score }}">{{ $scan->physique_score }}</span><span class="text-xl text-slate-500">/100</span></p>
                        <p class="text-xs text-slate-500">Relative, for tracking your own trend</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Overall impression</p>
                        <p class="mt-2 text-lg font-semibold">{{ \App\Models\BodyScan::LEANNESS[$a['visual_leanness'] ?? 'unclear'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Recorded</p>
                        <p class="mt-2 text-lg font-semibold">{{ $scan->weight_kg }} kg · {{ $scan->height_cm }} cm</p>
                    </div>
                </div>
                <p class="relative mt-8 text-lg leading-relaxed text-slate-200">{{ $scan->summary }}</p>
                @if (! empty($a['changes_since_last']))
                    <div class="relative mt-6 rounded-2xl border border-volt-400/30 bg-volt-400/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-volt-400">Since your previous scan</p>
                        <p class="mt-2 text-sm text-slate-200">{{ $a['changes_since_last'] }}</p>
                    </div>
                @endif
            </section>

            <section class="card p-6" data-intro>
                <h2 class="text-base font-semibold">Visible definition</h2>
                @php $levels = ['low' => 1, 'moderate' => 2, 'high' => 3]; @endphp
                <ul class="mt-5 space-y-5">
                    @foreach (['upper_body' => 'Upper body', 'core' => 'Core', 'lower_body' => 'Lower body'] as $key => $label)
                        @php $lvl = $a['visible_definition'][$key] ?? 'unclear'; @endphp
                        <li>
                            <div class="flex justify-between text-sm"><span class="text-slate-300">{{ $label }}</span><span class="text-slate-400">{{ ucfirst($lvl) }}</span></div>
                            <div class="mt-2 grid grid-cols-3 gap-1" aria-hidden="true">
                                @for ($i = 1; $i <= 3; $i++)<span class="h-1.5 rounded-full {{ $i <= ($levels[$lvl] ?? 0) ? 'bg-volt-400' : 'bg-ink-700' }}"></span>@endfor
                            </div>
                        </li>
                    @endforeach
                </ul>
                <h3 class="mt-8 text-sm font-semibold">Posture</h3>
                <p class="mt-2 text-sm text-slate-300">{{ $a['posture']['summary'] ?? '' }}</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-400">
                    @foreach ($a['posture']['observations'] ?? [] as $o)<li>· {{ $o }}</li>@endforeach
                </ul>
            </section>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="card p-6" data-intro>
                <h2 class="text-base font-semibold">Proportions & shape</h2>
                <p class="mt-3 text-sm text-slate-300">{{ $a['proportions'] ?? '' }}</p>
                <p class="mt-3 text-sm text-slate-300">{{ $a['body_shape'] ?? '' }}</p>
            </section>
            <section class="card p-6" data-intro>
                <h2 class="text-base font-semibold">Observations</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    @foreach ($a['observations'] ?? [] as $o)<li class="flex gap-3"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-400"></span>{{ $o }}</li>@endforeach
                </ul>
            </section>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            @foreach (['strengths' => ['Strengths', 'text-emerald-300'], 'focus_areas' => ['Focus areas', 'text-amber-300'], 'recommendations' => ['Recommendations', 'text-volt-400']] as $key => [$label, $color])
                <section class="card p-6" data-intro>
                    <h2 class="text-base font-semibold {{ $color }}">{{ $label }}</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-300">
                        @foreach ($a[$key] ?? [] as $line)
                            <li class="flex gap-3"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-current {{ $color }}"></span>{{ $line }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif

    {{-- Photos + photo checks --}}
    <section class="card mt-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-semibold">Photos</h2>
            <p class="text-xs text-slate-500">Originals are stored unchanged; these are privacy-safe display copies.</p>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-3 sm:max-w-xl">
            @foreach (\App\Models\BodyScan::VIEWS as $view)
                @if ($scan->photoPath($view))
                    <figure>
                        <img src="{{ route('media.scan', [$scan, $view]) }}" alt="{{ ucfirst($view) }} view" class="aspect-[3/4] w-full rounded-xl object-cover" loading="lazy">
                        <figcaption class="mt-1 text-center text-xs text-slate-500">{{ ucfirst($view) }}</figcaption>
                    </figure>
                @endif
            @endforeach
        </div>
        @if ($warnings->isNotEmpty() || ! empty($a['photo_consistency_tips']))
            <div class="mt-5 rounded-xl border border-amber-400/20 bg-amber-400/5 p-4">
                <p class="text-sm font-semibold text-amber-200">For your next scan</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-300">
                    @foreach ($warnings as $w)<li>· {{ $w }}</li>@endforeach
                    @foreach ($a['photo_consistency_tips'] ?? [] as $t)<li>· {{ $t }}</li>@endforeach
                </ul>
            </div>
        @endif
    </section>

    @if ($history->isNotEmpty())
        <section class="card mt-6 p-6">
            <h2 class="text-base font-semibold">Record</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($history as $log)
                    <li class="flex flex-wrap justify-between gap-2 text-slate-400"><span><span class="text-slate-200">{{ $log->actionLabel() }}</span>{{ $log->reason ? ' — ' . $log->reason : '' }}</span><time>{{ $log->created_at->format('d M Y, g:i a') }}</time></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
