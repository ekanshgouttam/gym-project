<x-public-layout title="Gallery">
    <x-page-hero eyebrow="Gallery" title="Inside the<br><span class='text-volt-400'>{{ config('app.name') }}</span> floor." lead="A look at the spaces where the work happens." />

    {{-- Placeholder artwork tiles. Replace each tile's background with a real photo (public/images/gallery/*.jpg). --}}
    <section class="pb-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="grid auto-rows-[180px] grid-cols-2 gap-4 md:auto-rows-[220px] md:grid-cols-4" data-stagger>
                @foreach ([
                    ['Strength floor', 'md:col-span-2 md:row-span-2', 'from-volt-400/30 via-ink-800 to-ink-900'],
                    ['Cardio deck', '', 'from-sky-500/30 via-ink-800 to-ink-900'],
                    ['Class studio', '', 'from-fuchsia-500/30 via-ink-800 to-ink-900'],
                    ['Turf & sleds', 'md:col-span-2', 'from-amber-400/25 via-ink-800 to-ink-900'],
                    ['Recovery', '', 'from-emerald-400/25 via-ink-800 to-ink-900'],
                    ['Body-scan booth', 'row-span-2', 'from-volt-400/25 via-ink-850 to-ink-950'],
                    ['Locker rooms', '', 'from-slate-400/20 via-ink-800 to-ink-900'],
                    ['Front desk', 'col-span-2', 'from-sky-400/20 via-ink-800 to-ink-900'],
                ] as [$caption, $span, $tint])
                    <figure class="group relative overflow-hidden rounded-2xl border border-ink-700 {{ $span }}">
                        <div class="absolute inset-0 bg-gradient-to-br {{ $tint }} transition duration-700 group-hover:scale-110"></div>
                        <div class="grid-bg absolute inset-0 opacity-60"></div>
                        <svg class="absolute right-4 top-4 h-8 w-8 text-white/10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 7v10M3 9.5v5M18 7v10M21 9.5v5M6 12h12"/></svg>
                        <figcaption class="absolute inset-x-0 bottom-0 translate-y-1 bg-gradient-to-t from-ink-950/90 to-transparent p-4 text-sm font-medium text-white transition group-hover:translate-y-0">{{ $caption }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="mt-8 text-center text-sm text-slate-500">Want to see it in person? <a href="{{ route('contact') }}" class="text-volt-400 hover:text-volt-300">Book a free tour</a>.</p>
        </div>
    </section>
</x-public-layout>
