<x-public-layout title="Facilities">
    <x-page-hero eyebrow="Facilities" title="A floor built<br>for <span class='text-volt-400'>serious</span> training." lead="Premium equipment, clean spaces and room to move at every branch." />

    <section class="pb-24">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6">
            @foreach ([
                ['Strength zone', 'Competition racks, calibrated plates, platforms and a full dumbbell run up to 50 kg.', ['Power racks', 'Deadlift platforms', 'Dumbbells to 50 kg', 'Plate-loaded machines'], 'from-volt-400/20'],
                ['Cardio deck', 'Treadmills, rowers, assault bikes and stair climbers with heart-rate tracking.', ['Curved treadmills', 'Concept2 rowers', 'Air bikes', 'Stair climbers'], 'from-sky-500/20'],
                ['Functional & studio', 'Open turf for sleds and conditioning, plus a studio for classes and mobility.', ['Sled track', 'Kettlebells', 'Group class studio', 'Mobility area'], 'from-fuchsia-500/20'],
                ['Recovery & amenities', 'Everything you need before and after a session.', ['Locker rooms & showers', 'Steam room', 'Body-scan booth', 'Supplement bar'], 'from-amber-400/20'],
            ] as $i => [$name, $desc, $items, $tint])
                <article class="card relative grid overflow-hidden md:grid-cols-5" data-reveal>
                    <div class="relative min-h-48 bg-gradient-to-br {{ $tint }} to-transparent md:col-span-2 {{ $i % 2 ? 'md:order-last' : '' }}">
                        <div class="grid-bg absolute inset-0"></div>
                        <span class="absolute bottom-4 left-6 font-display text-8xl font-bold text-white/10">0{{ $i + 1 }}</span>
                    </div>
                    <div class="p-8 md:col-span-3 md:p-10">
                        <h2 class="text-2xl font-bold sm:text-3xl">{{ $name }}</h2>
                        <p class="mt-3 text-slate-400">{{ $desc }}</p>
                        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ($items as $item)
                                <li class="flex items-center gap-3 text-sm text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-volt-400"></span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-public-layout>
