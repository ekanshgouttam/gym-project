<x-public-layout :title="$gym->name" :description="'Join ' . $gym->name . ($gym->city ? ' in ' . $gym->city : '') . ' on FlexFit. Scan the QR code or register online.'">
    @php $address = collect([$gym->address, $gym->city])->filter()->implode(', '); @endphp

    {{-- Branded hero --}}
    <section class="relative isolate overflow-hidden pt-32 pb-16 sm:pt-40">
        <div class="grid-bg absolute inset-0 -z-10 [mask-image:radial-gradient(ellipse_at_top,black_20%,transparent_70%)]"></div>
        <div data-parallax="-30" class="absolute -top-24 left-1/4 -z-10 h-80 w-80 rounded-full bg-volt-400/20 blur-[110px]"></div>
        <div data-parallax="-50" class="absolute top-20 right-0 -z-10 h-72 w-72 rounded-full bg-ember-500/15 blur-[110px]"></div>

        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_22rem]">
            <div>
                <div class="flex items-center gap-4" data-intro>
                    @if ($gym->logoUrl())
                        <img src="{{ $gym->logoUrl() }}" alt="{{ $gym->name }} logo" class="h-20 w-20 rounded-3xl border border-ink-700 bg-ink-850 object-contain p-2">
                    @else
                        <span class="grid h-20 w-20 place-items-center rounded-3xl bg-gradient-to-br from-volt-400 to-ember-500 font-display text-2xl font-bold text-ink-950">{{ strtoupper(substr($gym->name, 0, 2)) }}</span>
                    @endif
                    <span class="badge bg-volt-400/15 text-volt-300">On FlexFit</span>
                </div>
                <h1 data-split class="mt-6 text-4xl font-bold leading-[1.05] sm:text-6xl">{{ $gym->name }}</h1>
                @if ($address)<p data-intro class="mt-4 text-lg text-slate-400">{{ $address }}</p>@endif
                @if ($gym->setting('opening_hours'))<p data-intro class="mt-2 text-sm text-slate-400">🕒 {{ $gym->setting('opening_hours') }}</p>@endif
                @if ($gym->setting('about'))<p data-intro class="mt-4 max-w-xl text-slate-300">{{ $gym->setting('about') }}</p>@endif
                <div data-intro class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $gym->joinUrl() }}" class="btn-primary px-7 py-3.5 text-base">Join {{ $gym->name }}</a>
                    @if ($address)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($gym->name . ', ' . $address) }}" target="_blank" rel="noopener" class="btn-ghost px-7 py-3.5 text-base">Get directions</a>
                    @endif
                </div>
                <dl data-intro class="mt-10 flex flex-wrap gap-x-10 gap-y-4 text-sm">
                    @if ($members)<div><dt class="text-slate-500">Members</dt><dd class="font-display text-2xl font-bold text-white" data-count="{{ $members }}">{{ $members }}</dd></div>@endif
                    @if ($trainers->isNotEmpty())<div><dt class="text-slate-500">Trainers</dt><dd class="font-display text-2xl font-bold text-white">{{ $trainers->count() }}</dd></div>@endif
                    @if ($gym->phone)<div><dt class="text-slate-500">Phone</dt><dd class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $gym->phone) }}" class="text-white hover:text-volt-300">{{ $gym->phone }}</a></dd></div>@endif
                    @if ($gym->email)<div><dt class="text-slate-500">Email</dt><dd class="mt-1"><a href="mailto:{{ $gym->email }}" class="text-white hover:text-volt-300">{{ $gym->email }}</a></dd></div>@endif
                </dl>
            </div>

            {{-- Registration QR --}}
            <div class="card p-6 text-center" data-intro data-tilt>
                <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Scan to join</p>
                <div class="mx-auto mt-4 w-56 rounded-2xl bg-white p-3 [&>svg]:h-auto [&>svg]:w-full" role="img" aria-label="QR code that opens the {{ $gym->name }} registration page">{!! $qrSvg !!}</div>
                <p class="mt-4 text-sm text-slate-400">Point your phone camera here, or <a href="{{ $gym->joinUrl() }}" class="text-volt-400 hover:text-volt-300">register online</a>.</p>
                <p class="mt-2 break-all font-mono text-xs text-slate-500">{{ $gym->joinUrl() }}</p>
            </div>
        </div>
    </section>

    {{-- How joining works --}}
    <section class="border-t border-ink-700 py-20">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 sm:px-6 md:grid-cols-4" data-stagger>
            @foreach ([
                ['Scan or tap', 'Scan the QR code or tap Join.'],
                ['Verify mobile', 'Confirm your number with a one-time code.'],
                ['Profile & baseline', 'Your details and three photos for your AI baseline scan.'],
                ['Pick a plan', 'Pay online, or at the desk.'],
            ] as $i => [$t, $d])
                <div class="card p-5">
                    <span class="font-display text-2xl font-bold text-volt-400">{{ $i + 1 }}</span>
                    <h2 class="mt-2 font-semibold">{{ $t }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Plans --}}
    @if ($plans->isNotEmpty())
        <section class="border-t border-ink-700 py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Membership</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-5xl">Plans at {{ $gym->name }}</h2>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-3" data-stagger>
                    @foreach ($plans as $plan)
                        <x-plan-card :plan="$plan">
                            <x-slot name="cta"><a href="{{ $gym->joinUrl() }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} mt-8 w-full">Join with {{ $plan->name }}</a></x-slot>
                        </x-plan-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Trainers --}}
    @if ($trainers->isNotEmpty())
        <section class="border-t border-ink-700 py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="text-3xl font-bold sm:text-4xl" data-reveal>Meet the trainers</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4" data-stagger>
                    @foreach ($trainers as $trainer)
                        @include('public._trainer-card', ['trainer' => $trainer])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-4 pb-24 sm:px-6">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-volt-400 px-6 py-16 text-center text-ink-950 sm:px-16" data-reveal>
            <div class="grid-bg absolute inset-0 opacity-40 mix-blend-multiply"></div>
            <h2 class="relative text-3xl font-bold !text-ink-950 sm:text-5xl">Start your transformation at {{ $gym->name }}.</h2>
            <a href="{{ $gym->joinUrl() }}" class="btn relative mt-8 bg-ink-950 px-8 py-3.5 text-base text-white hover:bg-ink-800">Join now</a>
        </div>
    </section>
</x-public-layout>
