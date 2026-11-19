<x-public-layout title="About">
    <x-page-hero eyebrow="About FlexFit" title="Built for the people<br>who <span class='text-volt-400'>build</span> bodies." lead="Gym owners shouldn't need five apps and a paper register. Members shouldn't have to guess whether they're making progress. FlexFit fixes both." />

    <section class="border-y border-ink-700 bg-ink-900/50">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-ink-700 px-4 sm:px-6 lg:grid-cols-4 lg:divide-x">
            @foreach ([['Gyms', $stats['gyms']], ['Members', $stats['members']], ['Trainers', $stats['trainers']], ['AI scans', $stats['scans']]] as [$label, $value])
                <div class="px-4 py-10 text-center" data-reveal>
                    <p class="font-display text-4xl font-bold text-white sm:text-5xl" data-count="{{ $value }}">{{ $value }}</p>
                    <p class="mt-2 text-sm text-slate-400">{{ $label }} on FlexFit</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="py-24">
        <div class="mx-auto grid max-w-6xl gap-14 px-4 sm:px-6 lg:grid-cols-2">
            <div data-reveal>
                <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Our mission</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Make every gym feel premium, and every result visible.</h2>
            </div>
            <div class="space-y-5 text-lg text-slate-400" data-reveal>
                <p>Independent gyms run on passion and long hours. FlexFit takes the admin off their plate: registrations, fees, attendance and trainers in one place, with a dashboard that shows what's happening today.</p>
                <p>For members, the hardest part of training is not seeing change. Monthly AI body scans turn photos into honest, easy-to-read progress reports, so effort becomes visible and people keep showing up.</p>
            </div>
        </div>
    </section>

    <section class="border-t border-ink-700 py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold sm:text-4xl" data-reveal>What we believe</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-3" data-stagger>
                @foreach ([
                    ['Owners first', 'Every feature has to save a gym owner time or bring in members. If it doesn\'t, it doesn\'t ship.'],
                    ['Honest AI', 'Our AI describes what photos show. It never pretends to be a medical device or a body-fat scale.'],
                    ['Privacy by default', 'Each gym\'s data is separate. Body photos are private to the member and their gym.'],
                ] as [$t, $d])
                    <div data-tilt class="card p-7">
                        <h3 class="text-xl font-semibold">{{ $t }}</h3>
                        <p class="mt-3 text-slate-400">{{ $d }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 pb-24 sm:px-6">
        <div class="card mx-auto flex max-w-5xl flex-col items-center gap-6 p-10 text-center sm:flex-row sm:text-left" data-reveal>
            <div class="flex-1">
                <h2 class="text-2xl font-bold sm:text-3xl">Let's talk about your gym.</h2>
                <p class="mt-2 text-slate-400">Book a 20-minute demo or start your free trial now.</p>
            </div>
            <div class="flex shrink-0 gap-3">
                <a href="{{ route('demo') }}" class="btn-primary">Book a demo</a>
                <a href="{{ route('register') }}" class="btn-ghost">Register gym</a>
            </div>
        </div>
    </section>
</x-public-layout>
