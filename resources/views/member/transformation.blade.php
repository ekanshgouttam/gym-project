<x-app-layout title="My Transformation">
    @if ($scans->count() < 2)
        <x-empty-state title="Your comparison unlocks with your second scan" body="Your baseline is the starting point. After your next monthly scan you can compare any two scans side by side.">
            <a href="{{ route('member.scanner') }}" class="btn-primary">{{ $scans->isEmpty() ? 'Take your baseline scan' : 'Go to AI Body Scanner' }}</a>
        </x-empty-state>
        <div class="card mt-6 p-6">@include('partials.scan-guide')</div>
    @else
        @php
            $days = $facts['days'];
            $wd = $facts['weight_delta'];
            $an = optional($comparison)->analysis ?? [];
            $directionStyles = ['improved' => 'bg-emerald-500/15 text-emerald-300', 'similar' => 'bg-slate-500/15 text-slate-300', 'regressed' => 'bg-amber-500/15 text-amber-300', 'unclear' => 'bg-slate-500/15 text-slate-400'];
        @endphp

        {{-- Pick any two scans --}}
        <form method="GET" class="card grid gap-4 p-5 sm:grid-cols-[1fr_auto_1fr_auto] sm:items-end">
            <div>
                <label for="from" class="label">Before</label>
                <select id="from" name="from" class="input">
                    @foreach ($scans as $s)<option value="{{ $s->id }}" {{ $s->id === $from->id ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <span class="hidden pb-3 text-center text-slate-500 sm:block" aria-hidden="true">→</span>
            <div>
                <label for="to" class="label">After</label>
                <select id="to" name="to" class="input">
                    @foreach ($scans as $s)<option value="{{ $s->id }}" {{ $s->id === $to->id ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <button class="btn-primary">Compare</button>
        </form>

        {{-- Date range + recorded differences --}}
        <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div class="card p-5 xl:col-span-2" data-intro>
                <p class="text-sm text-slate-400">Comparison range</p>
                <p class="mt-2 font-display text-xl font-bold sm:text-2xl">{{ $from->created_at->format('d M Y') }} → {{ $to->created_at->format('d M Y') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $days }} days · {{ round($days / 30.4, 1) }} months</p>
            </div>
            <div class="card p-5" data-intro>
                <p class="text-sm text-slate-400">Weight difference</p>
                <p class="mt-2 font-display text-3xl font-bold">{{ $wd === null ? '—' : ($wd > 0 ? '+' : '') . $wd }}<span class="text-base text-slate-500"> kg</span></p>
                <p class="mt-1 text-xs text-slate-500">{{ $from->weight_kg }} → {{ $to->weight_kg }} kg</p>
            </div>
            <div class="card p-5" data-intro>
                <p class="text-sm text-slate-400">AI visual score</p>
                @if ($from->physique_score && $to->physique_score)
                    @php $sd = $to->physique_score - $from->physique_score; @endphp
                    <p class="mt-2 font-display text-3xl font-bold {{ $sd >= 0 ? 'text-emerald-300' : 'text-amber-300' }}">{{ $sd > 0 ? '+' : '' }}{{ $sd }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $from->physique_score }} → {{ $to->physique_score }}</p>
                @else
                    <p class="mt-2 font-display text-3xl font-bold text-slate-500">—</p>
                @endif
            </div>
        </div>

        {{-- Side-by-side + interactive slider per view --}}
        <section class="card mt-6 p-6" data-intro>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold">Before & after</h2>
                <p class="text-xs text-slate-500">Drag the slider. Original photos are never altered.</p>
            </div>
            <div class="mt-5 grid gap-8 lg:grid-cols-3">
                @foreach (\App\Models\BodyScan::VIEWS as $view)
                    @if ($from->photoPath($view) && $to->photoPath($view))
                        <div>
                            <p class="mb-2 text-center text-xs uppercase tracking-widest text-slate-500">{{ $view }}</p>
                            <div class="grid grid-cols-2 gap-2">
                                <figure><img src="{{ route('media.scan', [$from, $view]) }}" alt="{{ ucfirst($view) }}, {{ $from->created_at->format('d M Y') }}" class="aspect-[3/4] w-full rounded-lg object-cover" loading="lazy"><figcaption class="mt-1 text-center text-[11px] text-slate-500">{{ $from->created_at->format('d M Y') }}</figcaption></figure>
                                <figure><img src="{{ route('media.scan', [$to, $view]) }}" alt="{{ ucfirst($view) }}, {{ $to->created_at->format('d M Y') }}" class="aspect-[3/4] w-full rounded-lg object-cover" loading="lazy"><figcaption class="mt-1 text-center text-[11px] text-slate-500">{{ $to->created_at->format('d M Y') }}</figcaption></figure>
                            </div>
                            <div data-compare class="relative mt-3 aspect-[3/4] w-full select-none overflow-hidden rounded-2xl bg-ink-850">
                                <img src="{{ route('media.scan', [$to, $view]) }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <img data-compare-top src="{{ route('media.scan', [$from, $view]) }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <div data-compare-handle class="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-volt-400 shadow-[0_0_20px_rgb(var(--c-volt-400)/0.7)]">
                                    <span class="absolute left-1/2 top-1/2 grid h-9 w-9 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-volt-400 text-xs font-bold text-on-volt">⇆</span>
                                </div>
                                <span class="badge absolute left-3 top-3 bg-ink-950/70 text-white">Before</span>
                                <span class="badge absolute right-3 top-3 bg-volt-400 text-on-volt">After</span>
                                <input type="range" min="0" max="100" value="50" class="absolute inset-0 h-full w-full cursor-ew-resize opacity-0" aria-label="Compare {{ $view }} photos, before and after">
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            {{-- AI summary + observations --}}
            <section class="card p-6 xl:col-span-2" data-intro>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold">AI comparison</h2>
                    <span class="text-xs text-slate-500">Visual observations, not measurements</span>
                </div>
                @error('compare') <p class="field-error">{{ $message }}</p> @enderror

                @if ($comparison && $comparison->status === 'completed')
                    @if (isset($an['comparable']) && ! $an['comparable'])
                        <p class="mt-4 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">These photo sets aren't fully comparable, so treat the observations as tentative.</p>
                    @endif
                    <p class="mt-4 text-lg leading-relaxed text-slate-200">{{ $comparison->summary }}</p>
                    @if (! empty($an['comparability_notes']))<p class="mt-3 text-sm text-slate-400"><span class="text-slate-300">Photo conditions:</span> {{ $an['comparability_notes'] }}</p>@endif
                    <ul class="mt-6 divide-y divide-ink-700">
                        @foreach ($an['changes'] ?? [] as $ch)
                            <li class="flex gap-4 py-3">
                                <span class="badge h-fit shrink-0 {{ $directionStyles[$ch['direction']] ?? '' }}">{{ ucfirst($ch['direction']) }}</span>
                                <div><p class="text-sm font-medium text-white">{{ $ch['area'] }}</p><p class="text-sm text-slate-400">{{ $ch['observation'] }}</p></div>
                            </li>
                        @endforeach
                    </ul>
                    @if (! empty($an['posture_change']))<p class="mt-4 text-sm text-slate-300"><span class="font-medium text-white">Posture:</span> {{ $an['posture_change'] }}</p>@endif
                    @if (! empty($an['recommendations']))
                        <h3 class="mt-6 text-sm font-semibold text-volt-400">Next steps</h3>
                        <ul class="mt-2 space-y-1 text-sm text-slate-300">@foreach ($an['recommendations'] as $r)<li>· {{ $r }}</li>@endforeach</ul>
                    @endif
                @elseif ($comparison && $comparison->status === 'pending')
                    <div class="mt-6 flex items-center gap-4" data-poll="{{ route('member.transformation.status', $comparison) }}">
                        <span class="relative flex h-4 w-4"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-volt-400 opacity-60"></span><span class="relative inline-flex h-4 w-4 rounded-full bg-volt-400"></span></span>
                        <p class="text-sm text-slate-300">Comparing both photo sets… this page updates automatically.</p>
                    </div>
                @else
                    @if ($comparison && $comparison->status === 'failed')<p class="mt-4 text-sm text-amber-300">{{ $comparison->error }}</p>@endif
                    @if (! $aiEnabled)
                        <p class="mt-4 text-sm text-slate-400">AI comparisons are temporarily unavailable. The photos and recorded differences above are still accurate.</p>
                    @elseif ($limitReached)
                        <p class="mt-4 text-sm text-slate-400">You've reached today's limit of AI comparisons. Try again tomorrow.</p>
                    @else
                        <p class="mt-4 text-sm text-slate-400">Get an AI summary of the visible changes between these two scans.</p>
                        <form method="POST" action="{{ route('member.transformation.compare') }}" class="mt-4" data-busy="Starting…">
                            @csrf
                            <input type="hidden" name="from" value="{{ $from->id }}"><input type="hidden" name="to" value="{{ $to->id }}">
                            <button class="btn-primary">{{ $comparison ? 'Try again' : 'Generate AI comparison' }}</button>
                        </form>
                    @endif
                @endif
            </section>

            {{-- Measurement differences --}}
            <section class="card p-6" data-intro>
                <h2 class="text-base font-semibold">Measurement difference</h2>
                @if ($facts['measurements'])
                    <p class="mt-1 text-xs text-slate-500">Tape measurements from {{ \Illuminate\Support\Carbon::parse($facts['measured_on'][0])->format('d M') }} and {{ \Illuminate\Support\Carbon::parse($facts['measured_on'][1])->format('d M Y') }}</p>
                    <ul class="mt-4 divide-y divide-ink-700">
                        @foreach ($facts['measurements'] as $label => $m)
                            <li class="flex justify-between py-2.5 text-sm"><span class="text-slate-300">{{ $label }}</span><span><span class="text-slate-500">{{ $m['from'] }} → {{ $m['to'] }} cm</span> <span class="ml-2 font-medium {{ $m['delta'] <= 0 ? 'text-emerald-300' : 'text-sky-300' }}">{{ $m['delta'] > 0 ? '+' : '' }}{{ $m['delta'] }}</span></span></li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-sm text-slate-400">No tape measurements were logged within {{ \App\Support\ScanComparisonFacts::MEASUREMENT_WINDOW_DAYS }} days of both scans.</p>
                    <a href="{{ route('member.measurements') }}" class="btn-ghost mt-4">Log measurements</a>
                @endif
            </section>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="card p-5">
                <h2 class="text-base font-semibold">AI visual score trend</h2>
                <div class="mt-2" data-chart="line" data-height="240" data-series='@json($trend)'></div>
            </section>
            <section class="card p-6">@include('partials.scan-guide')</section>
        </div>
    @endif
</x-app-layout>
