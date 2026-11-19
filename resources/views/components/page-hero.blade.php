@props(['eyebrow', 'title', 'lead' => null])
{{-- Cinematic page header: grid, glow orbs with parallax, split-text headline. --}}
<section class="relative isolate overflow-hidden pt-36 pb-20 sm:pt-44 sm:pb-28">
    <div class="grid-bg absolute inset-0 -z-10 [mask-image:radial-gradient(ellipse_at_top,black_20%,transparent_70%)]"></div>
    <div data-parallax="-30" class="absolute -top-24 left-1/4 -z-10 h-80 w-80 rounded-full bg-volt-400/20 blur-[110px]"></div>
    <div data-parallax="-60" class="absolute top-10 right-0 -z-10 h-72 w-72 rounded-full bg-sky-500/10 blur-[110px]"></div>
    <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
        <p data-intro class="text-sm font-semibold uppercase tracking-[0.25em] text-volt-400">{{ $eyebrow }}</p>
        <h1 data-split class="mt-5 text-4xl font-bold leading-[1.05] sm:text-6xl lg:text-7xl">{!! $title !!}</h1>
        @if ($lead)
            <p data-intro class="mx-auto mt-6 max-w-2xl text-base text-slate-400 sm:text-lg">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
