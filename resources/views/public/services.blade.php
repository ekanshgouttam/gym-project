<x-public-layout title="Services">
    <x-page-hero eyebrow="Services" title="Everything you need<br>to <span class='text-volt-400'>transform</span>." lead="Coaching, programming, nutrition and AI tracking, all in one membership." />

    <section class="pb-24">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 md:grid-cols-2 lg:grid-cols-3" data-stagger>
            @foreach ([
                ['Personal training', 'One-to-one sessions with a certified coach who plans, spots and adjusts every workout.', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['AI body scans', 'A baseline at joining, then monthly photo scans with visual observations of posture, proportions and visible definition, plus side-by-side comparisons.', 'M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M8 12h8'],
                ['Workout programming', 'Structured weekly plans for fat loss, muscle gain, strength or endurance, visible in your member portal.', 'M6 7v10M3 9.5v5M18 7v10M21 9.5v5M6 12h12'],
                ['Diet planning', 'Calorie and macro targets with meal plans your trainer adjusts as your body changes.', 'M12 3c4 3 6 6 6 9a6 6 0 01-12 0c0-3 2-6 6-9z'],
                ['Group classes', 'High-energy HIIT, strength circuits, mobility and yoga sessions every day.', 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['Progress tracking', 'Weight, body measurements, attendance and scan history in one dashboard with charts.', 'M3 3v18h18M7 15l4-4 3 3 5-6'],
            ] as [$name, $desc, $icon])
                <article data-tilt class="card group relative overflow-hidden p-7">
                    <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-volt-400/0 blur-2xl transition duration-500 group-hover:bg-volt-400/20"></div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-volt-400/10 text-volt-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    </span>
                    <h2 class="mt-6 text-xl font-semibold">{{ $name }}</h2>
                    <p class="mt-3 text-slate-400">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-t border-ink-700 py-24">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6" data-reveal>
            <h2 class="text-3xl font-bold sm:text-4xl">All services are included with every membership.</h2>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('plans') }}" class="btn-primary">See plans</a>
                <a href="{{ route('contact') }}" class="btn-ghost">Talk to us</a>
            </div>
        </div>
    </section>
</x-public-layout>
