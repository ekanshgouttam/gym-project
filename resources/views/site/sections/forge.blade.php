{{-- 3D showcase: interactive dumbbell and weight plate (WebGL, lazy-loaded, paused off-screen) --}}
<section id="forge" class="relative overflow-hidden bg-coal-950 py-24 sm:py-32" aria-labelledby="forge-title">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_55%,rgba(255,77,18,.16),transparent_60%)]"></div>
    <p class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 select-none whitespace-nowrap text-center font-brand text-[26vw] leading-none s-outline opacity-40" aria-hidden="true" data-speed="0.85">FORGED</p>

    <div class="s-container relative grid items-center gap-10 lg:grid-cols-[1fr_1.8fr_0.8fr]">
        <div class="order-2 lg:order-1">
            <p class="s-eyebrow" data-reveal-up>The equipment</p>
            <h2 id="forge-title" class="s-h3 mt-5" data-reveal-up>Calibrated iron.<br>Competition racks.</h2>
            <p class="mt-5 max-w-xs text-zinc-500" data-reveal-up>Every plate is weighed, every bar knurled for grip. Move your mouse — or tilt your phone — to inspect.</p>
        </div>

        <div class="relative order-1 aspect-square w-full lg:order-2" data-cursor-text="Drag">
            <canvas class="absolute inset-0 h-full w-full touch-pan-y" data-forge-3d aria-label="Interactive 3D dumbbell and weight plate" role="img"></canvas>
            {{-- Shown if WebGL is unavailable, on low-power devices, or with reduced motion --}}
            <img src="{{ asset('media/site/program-strength-sm.webp') }}" alt="A dumbbell held in a strong grip" class="absolute inset-0 hidden h-full w-full rounded-full object-cover opacity-80 grayscale" loading="lazy" data-forge-fallback width="800" height="600">
        </div>

        <ul class="order-3 grid grid-cols-3 gap-4 lg:grid-cols-1 lg:gap-8">
            @foreach ($site['forge']['stats'] as [$n, $l])
                <li data-reveal-up><p class="font-brand text-4xl text-white sm:text-5xl">{{ $n }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ $l }}</p></li>
            @endforeach
        </ul>
    </div>
</section>
