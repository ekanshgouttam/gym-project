{{-- 2. About: editorial split with clip-path reveals, pillars and live counters --}}
@php $about = $site['about']; @endphp
<section id="about" class="s-section overflow-hidden bg-coal-950">
    <div class="s-container">
        <div class="grid gap-16 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <p class="s-eyebrow" data-reveal-up>About {{ $gym->name }}</p>
                <h2 class="s-h2 mt-6" data-split="lines">{{ $about['title'][0] }}<br><span class="text-blaze-500">{{ $about['title'][1] }}</span></h2>
                <p class="s-lead mt-8 max-w-2xl" data-reveal-up>{{ $about['lead'] }}</p>
                <p class="mt-5 max-w-2xl text-zinc-500" data-reveal-up>{{ $about['body'] }}</p>

                <ol class="mt-14 divide-y divide-white/[.07] border-y border-white/[.07]">
                    @foreach ($about['pillars'] as [$n, $title, $text])
                        <li class="group grid gap-2 py-6 sm:grid-cols-[4rem_1fr_1.4fr] sm:items-baseline sm:gap-6" data-reveal-up>
                            <span class="font-brand text-2xl text-blaze-500">{{ $n }}</span>
                            <h3 class="text-2xl transition-transform duration-500 group-hover:translate-x-2">{{ $title }}</h3>
                            <p class="text-zinc-500">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="relative mb-16 self-start lg:col-span-5 lg:mt-24">
                <figure class="s-card s-zoom aspect-[4/5] w-[78%]" data-reveal-clip>
                    <img src="{{ asset($about['images'][0]) }}" srcset="{{ asset(str_replace('.webp', '-sm.webp', $about['images'][0])) }} 800w, {{ asset($about['images'][0]) }} 1600w" sizes="(min-width: 1024px) 30vw, 75vw"
                         alt="Dumbbell and plate racks at {{ $gym->name }}" class="h-full w-full object-cover" loading="lazy" width="1600" height="2400" data-speed="0.92">
                </figure>
                <figure class="s-card s-zoom absolute -bottom-16 right-0 aspect-[3/4] w-[52%] border-4 border-coal-950" data-reveal-clip data-speed="1.08">
                    <img src="{{ asset($about['images'][1]) }}" srcset="{{ asset(str_replace('.webp', '-sm.webp', $about['images'][1])) }} 800w, {{ asset($about['images'][1]) }} 1600w" sizes="(min-width: 1024px) 22vw, 55vw"
                         alt="The training floor at night" class="h-full w-full object-cover" loading="lazy" width="1600" height="2400">
                </figure>
                <p class="absolute -left-8 top-0 hidden rotate-180 text-[0.65rem] font-semibold uppercase tracking-[0.5em] text-zinc-600 [writing-mode:vertical-rl] lg:block">{{ $gym->city ?: $gym->name }} · Since {{ $gym->created_at->format('Y') }}</p>
            </div>
        </div>

        <dl class="mt-28 grid grid-cols-2 gap-px overflow-hidden rounded-[1.75rem] border border-white/[.07] bg-white/[.07] lg:grid-cols-4">
            @foreach ([
                ['Active members', $stats['members'], '+'],
                ['Expert coaches', $stats['trainers'], ''],
                ['Kg lost by members', $stats['kg_lost'], ''],
                ['AI body scans', max($stats['scans'], 0), '+'],
            ] as [$label, $value, $suffix])
                <div class="bg-coal-950 p-6 sm:p-10" data-reveal-up>
                    <dd class="font-brand text-5xl text-white sm:text-7xl"><span data-count="{{ $value }}">{{ $value }}</span><span class="text-blaze-500">{{ $suffix }}</span></dd>
                    <dt class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ $label }}</dt>
                </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- Scroll-velocity marquee band --}}
<div class="overflow-hidden border-y border-white/[.06] bg-coal-900 py-6" aria-hidden="true">
    <div class="s-marquee font-brand text-5xl uppercase leading-none text-white/90 sm:text-7xl" data-marquee>
        @for ($i = 0; $i < 2; $i++)
            @foreach (collect($site['programs'])->pluck('name') as $word)
                <span>{{ $word }}</span><span class="s-outline">✦</span>
            @endforeach
        @endfor
    </div>
</div>
