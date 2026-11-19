{{-- 8. Member transformations: stories, live results and the AI body scan --}}
@php $r = $site['results']; @endphp
<section id="results" class="s-section overflow-hidden bg-coal-900">
    <div class="s-container">
        <div class="grid items-end gap-10 lg:grid-cols-2">
            <div>
                <p class="s-eyebrow" data-reveal-up>Member transformations</p>
                <h2 class="s-h2 mt-5" data-split="lines">Results you<br>can <span class="text-blaze-500">measure.</span></h2>
            </div>
            <p class="s-lead max-w-lg lg:justify-self-end" data-reveal-up>Every member gets a baseline AI body scan, then a new one every 30 days — front, side and back, compared side by side.</p>
        </div>

        <div class="mt-16 grid gap-5 lg:grid-cols-12">
            <figure class="s-card s-zoom relative min-h-[520px] lg:col-span-5" data-reveal-clip>
                <img src="{{ asset($r['image']) }}" srcset="{{ asset(str_replace('.webp', '-sm.webp', $r['image'])) }} 800w, {{ asset($r['image']) }} 1600w" sizes="(min-width: 1024px) 40vw, 100vw"
                     alt="An athlete after a training block" class="absolute inset-0 h-full w-full object-cover" loading="lazy" width="1600" height="2000">
                <div class="absolute inset-0 bg-gradient-to-t from-coal-950 via-transparent to-transparent"></div>
                <figcaption class="absolute inset-x-0 bottom-0 p-8">
                    <p class="font-brand text-7xl text-white"><span data-count="{{ $stats['kg_lost'] }}">{{ $stats['kg_lost'] }}</span><span class="text-blaze-500"> kg</span></p>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-300">lost by our members, tracked in the app</p>
                </figcaption>
            </figure>

            <div class="grid gap-5 sm:grid-cols-2 lg:col-span-7">
                @foreach ($r['stories'] as $s)
                    <article class="group flex flex-col justify-between rounded-[1.75rem] border border-white/[.07] bg-coal-950 p-7 transition-colors duration-500 hover:border-blaze-500/40 sm:p-8 {{ $loop->first ? 'sm:col-span-2' : '' }}" data-reveal-up>
                        <div>
                            <span class="inline-block rounded-full border border-white/15 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ $s['weeks'] }} weeks · {{ $s['goal'] }}</span>
                            <p class="mt-5 font-brand leading-none text-white transition-colors duration-500 group-hover:text-blaze-400 {{ $loop->first ? 'text-6xl sm:text-7xl' : 'text-[clamp(2.4rem,3.4vw,3.4rem)]' }}">{{ $s['change'] }}</p>
                        </div>
                        <blockquote class="mt-8 text-lg leading-snug text-zinc-300">“{{ $s['quote'] }}”</blockquote>
                        <p class="mt-6 text-sm text-zinc-500"><span class="font-semibold text-white">{{ $s['name'] }}, {{ $s['age'] }}</span></p>
                    </article>
                @endforeach
            </div>
        </div>

        {{-- AI body scan feature --}}
        <div class="mt-5 grid items-center gap-10 overflow-hidden rounded-[1.75rem] border border-white/[.07] bg-coal-950 p-8 sm:p-12 lg:grid-cols-[1.1fr_1fr]" data-reveal-up>
            <div>
                <p class="s-eyebrow">AI body scan</p>
                <h3 class="s-h3 mt-4">See the change, month by month.</h3>
                <p class="mt-4 max-w-lg text-zinc-500">Drag the slider to compare a baseline with a later scan. Reports describe visible changes in posture, proportions and definition — they are not medical or body-fat measurements.</p>
                <a href="#join" class="s-btn-primary mt-8" data-magnetic><span>Get your baseline scan</span><span class="s-arrow">→</span></a>
            </div>
            <div class="relative mx-auto aspect-[4/5] w-full max-w-sm select-none overflow-hidden rounded-[1.5rem]" data-compare data-cursor-text="Drag">
                {{-- Same photo on both sides: this demonstrates the comparison tool, not a real member's result. --}}
                <img src="{{ asset($r['image']) }}" alt="" class="absolute inset-0 h-full w-full object-cover blur-[1px] grayscale brightness-[.55] contrast-75" loading="lazy" width="800" height="1000">
                <div class="absolute inset-0" data-compare-after>
                    <img src="{{ asset($r['image']) }}" alt="Illustration of the before/after scan comparison" class="h-full w-full object-cover" loading="lazy" width="800" height="1000">
                </div>
                <span class="absolute left-3 top-3 rounded-full bg-coal-950/80 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-white">Baseline</span>
                <span class="absolute right-3 top-3 rounded-full bg-blaze-500 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-white">Month 6</span>
                <span class="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-white" style="left:50%" data-compare-handle><span class="absolute left-1/2 top-1/2 grid h-11 w-11 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white text-sm font-bold text-coal-950">⇆</span></span>
                <input type="range" min="0" max="100" value="50" class="absolute inset-0 h-full w-full cursor-ew-resize opacity-0" aria-label="Drag to try the scan comparison (illustration)">
                <span class="absolute inset-x-0 bottom-3 text-center text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-white/70">Illustration</span>
            </div>
        </div>
        <p class="mt-4 text-xs text-zinc-600">Member stories shown are examples; results vary with training, nutrition and consistency.</p>
    </div>
</section>
