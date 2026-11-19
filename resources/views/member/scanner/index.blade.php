<x-app-layout title="AI Body Scanner">
    @php $e = $eligibility; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card relative overflow-hidden p-6 sm:p-8 xl:col-span-2" data-intro>
            <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-volt-400/15 blur-3xl"></div>
            <div class="relative">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $e['first_scan'] ? 'Baseline body scan' : 'Monthly body scan' }}</h2>
                        <p class="mt-1 text-sm text-slate-400">Front, side and back photos. The AI describes visible posture, proportions and shape, and compares them with your earlier scans.</p>
                    </div>
                    <span class="badge {{ $e['eligible'] ? 'bg-volt-400 text-ink-950' : 'bg-ink-700 text-slate-300' }}">{{ $e['eligible'] ? 'Upload open' : 'Upload locked' }}</span>
                </div>

                {{-- Eligibility / countdown --}}
                @if (! $e['eligible'] && $e['next_at'])
                    <div class="mt-6 rounded-2xl border border-ink-600 bg-ink-850 p-5 sm:p-6">
                        <p class="text-sm text-slate-400">Next scan available</p>
                        <p class="mt-1 font-display text-xl font-bold text-white">{{ $e['next_at']->format('l, d M Y \a\t g:i a') }}</p>
                        <div class="mt-5 grid grid-cols-4 gap-2 text-center" data-countdown="{{ $e['next_at']->toIso8601String() }}" role="timer" aria-live="off">
                            @foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Min', 'seconds' => 'Sec'] as $unit => $label)
                                <div class="rounded-xl bg-ink-900 py-3">
                                    <p class="font-display text-3xl font-bold text-volt-400 tabular-nums" data-unit="{{ $unit }}">--</p>
                                    <p class="text-[11px] uppercase tracking-widest text-slate-500">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-slate-500">{{ $e['reason'] }} Last accepted scan: {{ $e['last']->created_at->format('d M Y, g:i a') }}.</p>
                    </div>
                @elseif (! $e['eligible'])
                    <div class="mt-6 rounded-2xl border border-ink-600 bg-ink-850 p-5 text-sm text-slate-300">
                        {{ $e['reason'] }}
                        @unless (auth()->user()->hasActiveMembership())<a href="{{ route('member.membership') }}" class="btn-primary mt-4 flex w-fit">Activate membership</a>@endunless
                    </div>
                @elseif ($e['override'])
                    <p class="mt-6 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">Your gym has allowed one early scan (until {{ $e['override']->expires_at->format('d M Y') }}).</p>
                @endif

                @unless ($aiEnabled)
                    <p class="mt-6 rounded-xl border border-ink-600 bg-ink-850 px-4 py-3 text-sm text-slate-400">AI analysis is temporarily unavailable. You can still upload: your photos are saved and analysed when it's back.</p>
                @endunless

                {{-- Upload form: rendered always, disabled until eligible (enforced on the server too). --}}
                <form method="POST" action="{{ route('member.scanner.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 {{ $e['eligible'] ? '' : 'pointer-events-none select-none opacity-40' }}" data-busy="Checking photos…" novalidate @unless($e['eligible']) aria-disabled="true" inert @endunless>
                    @csrf
                    <fieldset {{ $e['eligible'] ? '' : 'disabled' }} class="space-y-6">
                        <legend class="sr-only">Scan photos</legend>
                        @include('partials.scan-photo-inputs', ['disabled' => ! $e['eligible']])
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="weight_kg" class="label">Current weight (kg)</label>
                                <input id="weight_kg" name="weight_kg" type="number" step="0.1" inputmode="decimal" value="{{ old('weight_kg', $currentWeight) }}" class="input" required>
                                @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="height_cm" class="label">Height (cm)</label>
                                <input id="height_cm" name="height_cm" type="number" step="0.1" inputmode="decimal" value="{{ old('height_cm', $height) }}" class="input" required>
                                @error('height_cm') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <label class="flex items-start gap-3 text-sm text-slate-400">
                            <input type="checkbox" name="consent" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" required>
                            I agree to my photos being analysed by AI. Photos are stored privately and only visible to me and my gym's staff.
                        </label>
                        @error('consent') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>
                    <button type="submit" class="btn-primary w-full sm:w-auto" data-scan-submit {{ $e['eligible'] ? '' : 'disabled' }}>
                        {{ $e['eligible'] ? 'Upload & analyse' : 'Locked until ' . optional($e['next_at'])->format('d M') }}
                    </button>
                </form>
            </div>
        </section>

        <aside class="space-y-6">
            @include('partials.scan-guide', ['class' => 'card p-6'])
            <p class="card p-5 text-xs leading-relaxed text-slate-400">
                <strong class="text-slate-200">About AI reports.</strong> Reports describe what's visible in photos: posture, proportions, shape and visible definition. They are not body-fat, muscle-mass or medical measurements, and lighting or pose can change how a photo looks.
            </p>
        </aside>
    </div>

    <section class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between p-5">
            <h2 class="text-base font-semibold">Scan history</h2>
            @if ($scans->whereNull('voided_at')->count() >= 2)<a href="{{ route('member.transformation') }}" class="text-sm text-volt-400 hover:text-volt-300">Compare scans →</a>@endif
        </div>
        <ul class="divide-y divide-ink-700 border-t border-ink-700">
            @forelse ($scans as $scan)
                <li>
                    <a href="{{ route('member.scanner.show', $scan) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-ink-850/50 {{ $scan->isVoided() ? 'opacity-60' : '' }}">
                        <div>
                            <p class="text-sm font-medium text-white">{{ $scan->created_at->format('d M Y') }} @if($scan->is_baseline)<span class="badge ml-1 bg-volt-400/15 text-volt-300">Baseline</span>@endif</p>
                            <p class="text-xs text-slate-500">{{ $scan->weight_kg }} kg</p>
                        </div>
                        @if ($scan->isVoided())
                            <span class="badge bg-slate-500/15 text-slate-400">Voided</span>
                        @elseif ($scan->status === 'completed')
                            <span class="text-sm text-slate-200">Visual score {{ $scan->physique_score }}/100</span>
                        @elseif ($scan->status === 'pending')
                            <span class="badge bg-sky-500/15 text-sky-300">Analysing…</span>
                        @else
                            <span class="badge bg-amber-500/15 text-amber-300">Analysis pending</span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">No scans yet. Your first scan is your baseline.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
