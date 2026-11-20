<x-public-layout title="Gym management software with AI body transformation">
    {{-- Hero --}}
    <section class="relative isolate flex min-h-[100svh] items-center overflow-hidden pt-16">
        <div class="grid-bg absolute inset-0 -z-20 [mask-image:radial-gradient(ellipse_at_center,black_25%,transparent_75%)]"></div>
        <div class="absolute left-1/2 top-1/2 -z-20 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-volt-400/15 blur-[140px]"></div>
        <div data-parallax="-40" class="absolute -right-20 top-1/4 -z-20 h-72 w-72 rounded-full bg-ember-500/20 blur-[120px]"></div>
        <canvas data-hero-3d class="absolute inset-0 -z-10 h-full w-full opacity-80" aria-hidden="true"></canvas>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-ink-950 to-transparent"></div>

        <div class="mx-auto w-full max-w-6xl px-4 py-20 text-center sm:px-6">
            <p data-intro class="mx-auto inline-flex items-center gap-2 rounded-full border border-volt-400/30 bg-ink-950/60 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-volt-300 backdrop-blur">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-volt-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-volt-400"></span></span>
                Gym management + AI body transformation
            </p>
            <h1 data-split class="mt-8 text-5xl font-bold leading-[0.98] tracking-tight sm:text-7xl lg:text-8xl">Run your gym.<br><span class="text-volt-400">Prove</span> the results.</h1>
            <p data-intro class="mx-auto mt-8 max-w-2xl text-lg text-slate-300">
                FlexFit gives every gym its own QR sign-up, member app, fees, attendance and trainers, plus monthly AI body scans that show members how they're changing.
            </p>
            <div data-intro class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn-primary px-7 py-3.5 text-base">Register your gym{{ $trialDays ? ' — ' . $trialDays . ' days free' : '' }}</a>
                <a href="{{ route('demo') }}" class="btn-ghost bg-ink-950/40 px-7 py-3.5 text-base backdrop-blur">Book a demo</a>
            </div>
            <p data-intro class="mt-6 text-sm text-slate-500">No card needed. Set up in two minutes.</p>
        </div>

        <a href="#stats" class="absolute bottom-8 left-1/2 -translate-x-1/2 text-slate-500 hover:text-white" aria-label="Scroll down">
            <svg class="h-6 w-6 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        </a>
    </section>

    {{-- Live platform numbers --}}
    <section id="stats" class="border-y border-ink-700 bg-ink-900/50">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-ink-700 px-4 sm:px-6 lg:grid-cols-4 lg:divide-x">
            @foreach ([
                ['Gyms on FlexFit', $stats['gyms'], ''],
                ['Members managed', $stats['members'], '+'],
                ['Trainers', $stats['trainers'], ''],
                ['AI scans completed', $stats['scans'], '+'],
            ] as [$label, $value, $suffix])
                <div class="px-4 py-10 text-center" data-reveal>
                    <p class="font-display text-4xl font-bold text-white sm:text-5xl"><span data-count="{{ $value }}">{{ $value }}</span><span class="text-volt-400">{{ $suffix }}</span></p>
                    <p class="mt-2 text-sm text-slate-400">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="overflow-hidden border-b border-ink-700 py-6" aria-hidden="true">
        <div data-marquee class="flex w-max gap-12 whitespace-nowrap font-display text-3xl font-bold text-ink-600 sm:text-5xl">
            @for ($i = 0; $i < 2; $i++)
                @foreach (['QR Registration', 'Member App', 'Fees', 'Attendance', 'Trainers', 'Workouts', 'Diet', 'AI Body Scans', 'Reports'] as $word)
                    <span>{{ $word }}</span><span class="text-volt-400/60">✦</span>
                @endforeach
            @endfor
        </div>
    </div>

    {{-- The complete workflow --}}
    <section class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center" data-reveal>
                <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">The complete workflow</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-5xl">From sign-up to six-pack, in one place.</h2>
                <p class="mt-4 text-lg text-slate-400">Every step of running a gym, connected. No spreadsheets, no paper registers.</p>
            </div>

            <div class="relative mx-auto mt-16 max-w-4xl">
                <div class="absolute left-[1.15rem] top-2 bottom-2 w-px bg-ink-700 sm:left-1/2" aria-hidden="true"></div>
                <div data-progress-line class="absolute left-[1.15rem] top-2 bottom-2 w-px origin-top bg-gradient-to-b from-volt-400 to-ember-500 sm:left-1/2" aria-hidden="true"></div>
                <ol class="space-y-10">
                    @foreach ([
                        ['Register your gym', 'Add your gym name, logo and address. Your own dashboard is ready instantly.', 'Owner'],
                        ['Get your unique QR code', 'Print it for the front desk. It links straight to your branded sign-up page.', 'Owner'],
                        ['Members scan and join', 'They verify their mobile with an OTP, get a Member ID and choose a plan.', 'Member'],
                        ['Collect fees', 'Members pay online, or you approve cash payments at the desk.', 'Owner'],
                        ['Track attendance and training', 'Attendance, trainers, workout and diet plans, all linked to each member.', 'Owner'],
                        ['Monthly AI body scans', 'Front, side and back photos turn into a visual progress report every 30 days.', 'Member'],
                        ['See the transformation', 'Before/after slider, AI comparisons and a month-by-month history keep members motivated.', 'Member'],
                    ] as $i => [$title, $text, $who])
                        <li class="relative grid items-center gap-4 pl-12 sm:grid-cols-2 sm:gap-16 sm:pl-0" data-reveal>
                            <span class="absolute left-0 top-1 grid h-9 w-9 place-items-center rounded-full border border-volt-400/40 bg-ink-950 font-display text-sm font-bold text-volt-400 sm:left-1/2 sm:top-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2">{{ $i + 1 }}</span>
                            <div class="{{ $i % 2 ? 'sm:order-2' : 'sm:text-right' }}">
                                <span class="badge {{ $who === 'Owner' ? 'bg-volt-400/15 text-volt-300' : 'bg-ember-500/15 text-ember-300' }}">{{ $who }}</span>
                                <h3 class="mt-2 text-xl font-semibold">{{ $title }}</h3>
                            </div>
                            <p class="text-slate-400 {{ $i % 2 ? 'sm:order-1 sm:text-right' : '' }}">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
            <p class="mt-14 text-center"><a href="{{ route('how-it-works') }}" class="btn-ghost">See how it works in detail</a></p>
        </div>
    </section>

    {{-- Three panels --}}
    <section class="border-t border-ink-700 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="max-w-2xl" data-reveal>
                <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Three connected apps</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-5xl">One for you, one for your members.</h2>
            </div>
            <div class="mt-14 grid gap-6 lg:grid-cols-3" data-stagger>
                {{-- Owner panel mock --}}
                <article data-tilt class="card overflow-hidden p-6 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Gym owner panel</p>
                            <h3 class="mt-2 text-2xl font-bold">Your whole gym at a glance.</h3>
                        </div>
                        <span class="hidden gap-1.5 sm:flex" aria-hidden="true"><i class="h-2.5 w-2.5 rounded-full bg-ink-600"></i><i class="h-2.5 w-2.5 rounded-full bg-ink-600"></i><i class="h-2.5 w-2.5 rounded-full bg-volt-400"></i></span>
                    </div>
                    <div class="mt-6 grid grid-cols-3 gap-3" aria-hidden="true">
                        @foreach ([['Members', '248'], ['Due this week', '₹38,400'], ['Check-ins today', '96']] as [$l, $v])
                            <div class="rounded-xl border border-ink-700 bg-ink-850 p-3">
                                <p class="text-[11px] text-slate-500">{{ $l }}</p>
                                <p class="mt-1 font-display text-lg font-bold text-white sm:text-2xl">{{ $v }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex h-40 items-end gap-2 rounded-xl border border-ink-700 bg-ink-850 p-4" aria-hidden="true">
                        @foreach ([38, 52, 45, 63, 58, 72, 66, 81, 77, 88, 84, 95] as $h)
                            <span data-bar="{{ $h }}" class="flex-1 rounded-t-md bg-gradient-to-t from-volt-500/40 to-volt-400" style="height: {{ $h }}%"></span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm text-slate-400">Members, plans and fees, attendance, trainers, workout and diet plans, AI scans and reports.</p>
                </article>

                {{-- Member app mock --}}
                <article data-tilt class="card relative overflow-hidden p-6">
                    <p class="text-sm font-semibold uppercase tracking-widest text-ember-400">Member app</p>
                    <h3 class="mt-2 text-2xl font-bold">Made for phones.</h3>
                    <div class="mx-auto mt-6 w-48 rounded-[2rem] border-4 border-ink-700 bg-ink-950 p-3 shadow-2xl" aria-hidden="true">
                        <div class="mx-auto h-1.5 w-12 rounded-full bg-ink-700"></div>
                        <p class="mt-3 text-[10px] text-slate-500">Next scan unlocks in</p>
                        <p class="font-display text-xl font-bold text-volt-400">12d 04h</p>
                        <div class="mt-3 space-y-1.5">
                            @foreach (['Membership · Active', 'Workout · Push day', 'Diet · 2,200 kcal'] as $row)
                                <div class="rounded-lg bg-ink-850 px-2 py-1.5 text-[10px] text-slate-300">{{ $row }}</div>
                            @endforeach
                        </div>
                        <div class="mt-3 rounded-lg bg-volt-400 py-1.5 text-center text-[10px] font-semibold text-on-volt">AI Body Scanner</div>
                    </div>
                    <p class="mt-6 text-sm text-slate-400">Profile, membership, attendance, workouts, diet, payments, notifications and AI scans.</p>
                </article>
            </div>
            <p class="mt-6 text-sm text-slate-500" data-reveal>Plus the FlexFit Super Admin panel that runs the whole platform: gyms, subscriptions, revenue, AI costs and support.</p>
        </div>
    </section>

    {{-- AI transformation --}}
    <section class="border-t border-ink-700 py-24 sm:py-32">
        <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2">
            <div data-reveal>
                <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">AI body transformation</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-5xl">Members see progress, not just the scale.</h2>
                <p class="mt-5 text-lg text-slate-400">Every 30 days members upload front, side and back photos. The first becomes their baseline. AI describes visible changes in posture, proportions and definition, and compares matching angles side by side.</p>
                <ul class="mt-6 space-y-2 text-slate-300">
                    <li>✦ Before/after slider for each angle</li>
                    <li>✦ AI visual comparison and summary reports</li>
                    <li>✦ Monthly history with a countdown to the next scan</li>
                </ul>
                <p class="mt-6 rounded-xl border border-ink-700 bg-ink-900/70 p-4 text-sm text-slate-400">Reports are visual observations from photos. They are not body-fat, muscle-mass or medical measurements.</p>
            </div>
            <div data-reveal>
                <div data-ba class="relative mx-auto aspect-[3/4] w-full max-w-sm overflow-hidden rounded-3xl border border-ink-700 bg-ink-850 select-none">
                    {{-- Illustrative silhouettes, not real photos --}}
                    <svg viewBox="0 0 300 400" class="absolute inset-0 h-full w-full" aria-hidden="true">
                        <rect width="300" height="400" fill="#10141C"/>
                        <g fill="#374152"><circle cx="150" cy="70" r="30"/><path d="M95 120 Q150 100 205 120 L222 250 Q150 275 78 250 Z"/><rect x="100" y="250" width="40" height="130" rx="18"/><rect x="160" y="250" width="40" height="130" rx="18"/><rect x="58" y="125" width="30" height="120" rx="15"/><rect x="212" y="125" width="30" height="120" rx="15"/></g>
                    </svg>
                    <div data-ba-after class="absolute inset-0">
                        <svg viewBox="0 0 300 400" class="h-full w-full" aria-hidden="true">
                            <rect width="300" height="400" fill="#151A23"/>
                            <g fill="#FFD400" fill-opacity=".85"><circle cx="150" cy="70" r="30"/><path d="M88 118 Q150 98 212 118 L206 245 Q150 262 94 245 Z"/><rect x="104" y="245" width="38" height="135" rx="18"/><rect x="158" y="245" width="38" height="135" rx="18"/><rect x="52" y="122" width="34" height="118" rx="16"/><rect x="214" y="122" width="34" height="118" rx="16"/></g>
                        </svg>
                    </div>
                    <span class="badge absolute left-3 top-3 bg-ink-950/80 text-white">Baseline</span>
                    <span class="badge absolute right-3 top-3 bg-volt-400 text-on-volt">Month 4</span>
                    <span data-ba-handle class="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-white/80" style="left: 50%"><span class="absolute top-1/2 left-1/2 grid h-9 w-9 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white text-xs font-bold text-on-volt">⇆</span></span>
                    <input type="range" min="0" max="100" value="50" class="absolute inset-0 h-full w-full cursor-ew-resize opacity-0" aria-label="Drag to compare baseline and month 4 (illustration)">
                </div>
                <p class="mt-3 text-center text-xs text-slate-500">Illustration. Drag to compare.</p>
            </div>
        </div>
    </section>

    {{-- Pricing teaser --}}
    @if ($plans->isNotEmpty())
        <section class="border-t border-ink-700 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Pricing</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-5xl">Simple plans that grow with you.</h2>
                    <div data-billing-toggle class="mx-auto mt-8 inline-flex rounded-full border border-ink-700 bg-ink-900 p-1" role="group" aria-label="Billing period">
                        <button type="button" data-billing="monthly" class="rounded-full px-5 py-2 text-sm font-semibold transition">Monthly</button>
                        <button type="button" data-billing="yearly" class="rounded-full px-5 py-2 text-sm font-semibold transition">Yearly <span class="text-xs opacity-70">2 months free</span></button>
                    </div>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-3" data-stagger>
                    @foreach ($plans as $plan)
                        <x-saas-plan-card :plan="$plan" :featured="$plans->count() > 2 && $loop->index === 1" :trial-days="$trialDays" />
                    @endforeach
                </div>
                <p class="mt-8 text-center"><a href="{{ route('pricing') }}" class="text-sm font-semibold text-volt-400 hover:text-volt-300">Compare plans and FAQs →</a></p>
            </div>
        </section>
    @endif

    {{-- Gyms on FlexFit --}}
    @if ($gyms->isNotEmpty())
        <section class="border-t border-ink-700 py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">On FlexFit</p>
                        <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Gyms already training with us.</h2>
                    </div>
                    <a href="{{ route('gyms') }}" class="btn-ghost">Find a gym</a>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-stagger>
                    @foreach ($gyms as $gym)
                        @include('public._gym-card', ['gym' => $gym])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="px-4 pb-24 sm:px-6">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-volt-400 px-6 py-20 text-center text-on-volt sm:px-16" data-reveal>
            <div class="grid-bg absolute inset-0 opacity-40 mix-blend-multiply"></div>
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-ember-500/60 blur-3xl" aria-hidden="true"></div>
            <h2 class="relative text-4xl font-bold !text-on-volt sm:text-6xl">Your gym, live in two minutes.</h2>
            <p class="relative mx-auto mt-4 max-w-xl text-lg text-ink-800">Register, print your QR code, and your first members can join today.</p>
            <div class="relative mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn bg-ink-950 px-8 py-3.5 text-base text-white hover:bg-ink-800">Register your gym</a>
                <a href="{{ route('demo') }}" class="btn border border-ink-950/30 px-8 py-3.5 text-base text-on-volt hover:bg-ink-950/10">Book a demo</a>
            </div>
        </div>
    </section>
</x-public-layout>
