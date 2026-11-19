{{-- 1. Hero: cinematic video, split headline, magnetic CTAs, live stats --}}
@php $hero = $site['hero']; @endphp
<section id="home" class="relative isolate flex min-h-[100svh] flex-col justify-end overflow-hidden bg-coal-950" data-hero>
    <div class="absolute inset-0 -z-20" data-speed="0.7">
        <picture>
            <source media="(max-width: 767px)" srcset="{{ asset($hero['poster_mobile']) }}">
            <img src="{{ asset($hero['poster']) }}" alt="" class="s-hero-media h-full w-full object-cover" width="1600" height="1142" fetchpriority="high" data-hero-poster>
        </picture>
        <video class="s-hero-media absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-[1.5s]" muted loop playsinline preload="none"
               data-hero-video data-src="{{ asset($hero['video']) }}" data-src-mobile="{{ asset($hero['video_mobile']) }}" aria-hidden="true"></video>
    </div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-coal-950 via-coal-950/40 to-coal-950/70"></div>
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_20%_100%,rgba(255,77,18,.22),transparent_55%)]"></div>

    <div class="s-container pb-24 pt-[calc(var(--header-h)+3rem)] sm:pb-14">
        <p class="s-eyebrow" data-hero-fade>{{ $hero['eyebrow'] }}</p>
        <h1 class="s-display mt-6" data-split="lines" aria-label="{{ implode(' ', $hero['headline']) }}">
            <span class="block">{{ $hero['headline'][0] }}</span>
            <span class="block text-white">{{ $hero['headline'][1] }}</span>
            <span class="block">{{ rtrim($hero['headline'][2], '.') }}<span class="text-blaze-500">.</span></span>
        </h1>

        <div class="mt-10 grid items-end gap-10 lg:grid-cols-[1fr_auto]">
            <div>
                <p class="max-w-md text-lg text-zinc-300 sm:text-xl" data-hero-fade>{{ $hero['subtitle'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row" data-hero-fade>
                    <a href="#programs" class="s-btn-primary" data-magnetic><span>Explore programs</span><span class="s-arrow">→</span></a>
                    <a href="#join" class="s-btn-ghost" data-magnetic><span>Join our gym</span></a>
                </div>
            </div>
            <dl class="grid grid-cols-3 gap-6 border-t border-white/10 pt-6 sm:gap-10 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0" data-hero-fade>
                <div><dt class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-zinc-500">Members</dt><dd class="mt-1 font-brand text-4xl text-white sm:text-5xl"><span data-count="{{ max($stats['members'], 1) }}">{{ $stats['members'] }}</span><span class="text-blaze-500">+</span></dd></div>
                <div><dt class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-zinc-500">Coaches</dt><dd class="mt-1 font-brand text-4xl text-white sm:text-5xl"><span data-count="{{ $stats['trainers'] }}">{{ $stats['trainers'] }}</span></dd></div>
                <div><dt class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-zinc-500">Kg lost</dt><dd class="mt-1 font-brand text-4xl text-white sm:text-5xl"><span data-count="{{ $stats['kg_lost'] }}">{{ $stats['kg_lost'] }}</span></dd></div>
            </dl>
        </div>

        <a href="#about" class="group mt-12 hidden items-center gap-4 text-xs font-semibold uppercase tracking-[0.3em] text-zinc-400 hover:text-white sm:inline-flex" data-hero-fade>
            <span class="relative h-12 w-7 rounded-full border border-white/30"><span class="absolute left-1/2 top-2 h-2 w-1 -translate-x-1/2 rounded-full bg-blaze-500" data-scroll-dot></span></span>
            Scroll to explore
        </a>
    </div>
</section>
