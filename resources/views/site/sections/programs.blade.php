{{-- 4. Programs: pinned horizontal scroll on desktop, swipeable rail on touch screens --}}
<section id="programs" class="relative bg-coal-900" data-programs>
    <div class="flex min-h-[100svh] flex-col justify-center overflow-hidden py-24 lg:py-0" data-programs-pin>
        <div class="s-container flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="s-eyebrow" data-reveal-up>Fitness programs</p>
                <h2 class="s-h2 mt-5" data-split="lines">Pick your <span class="text-blaze-500">fight.</span></h2>
            </div>
            <p class="max-w-sm text-zinc-500" data-reveal-up>Six coached programs, scheduled from 5:30 am. Every membership includes them all.</p>
        </div>

        <div class="mt-12 overflow-x-auto overscroll-x-contain [scrollbar-width:none] lg:overflow-visible [&::-webkit-scrollbar]:hidden" data-programs-scroller>
            <ul class="flex w-max snap-x snap-mandatory gap-5 px-5 sm:px-8 lg:gap-8 lg:px-12" data-programs-track>
                @foreach ($site['programs'] as $i => $p)
                    <li class="s-card s-zoom group relative h-[62svh] max-h-[640px] min-h-[420px] w-[78vw] shrink-0 snap-center sm:w-[46vw] lg:w-[32vw] xl:w-[27vw]" data-cursor-text="Book">
                        <img src="{{ asset($p['image']) }}" srcset="{{ asset(str_replace('.webp', '-sm.webp', $p['image'])) }} 800w, {{ asset($p['image']) }} 1600w" sizes="(min-width: 1024px) 32vw, 78vw"
                             alt="{{ $p['name'] }} training" class="absolute inset-0 h-full w-full object-cover grayscale transition-[filter] duration-700 group-hover:grayscale-0" loading="lazy" width="800" height="1000">
                        <div class="absolute inset-0 bg-gradient-to-t from-coal-950 via-coal-950/50 to-coal-950/10"></div>
                        <div class="relative flex h-full flex-col justify-between p-7">
                            <div class="flex items-start justify-between">
                                <span class="font-brand text-6xl text-white/20 transition-colors duration-500 group-hover:text-blaze-500">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="rounded-full border border-white/20 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-zinc-300 backdrop-blur">{{ $p['level'] }}</span>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blaze-400">{{ $p['tag'] }}</p>
                                <h3 class="mt-2 text-[clamp(2.4rem,3.6vw,3.75rem)] leading-none">{{ $p['name'] }}</h3>
                                <p class="mt-3 max-h-0 overflow-hidden text-zinc-300 opacity-0 transition-all duration-700 group-hover:max-h-32 group-hover:opacity-100 max-lg:max-h-32 max-lg:opacity-100">{{ $p['text'] }}</p>
                                <div class="mt-5 flex items-center justify-between border-t border-white/15 pt-4 text-sm">
                                    <span class="text-zinc-400">{{ $p['duration'] }} sessions</span>
                                    <a href="#join" class="font-semibold uppercase tracking-[0.15em] text-white hover:text-blaze-400" data-interest="{{ $p['name'] }}">Try free →</a>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
                <li class="flex w-[60vw] shrink-0 snap-center items-center sm:w-[36vw] lg:w-[24vw]" aria-hidden="true">
                    <p class="font-brand text-6xl uppercase leading-[.9] text-white/10 lg:text-8xl">No<br>excuses<span class="text-blaze-500">.</span></p>
                </li>
            </ul>
        </div>
        <div class="s-container mt-8 lg:hidden"><div class="h-px bg-white/10"><div class="h-px w-1/4 bg-blaze-500" data-programs-progress></div></div></div>
    </div>
</section>
