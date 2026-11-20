<x-public-layout title="Book a demo">
    <section class="relative isolate overflow-hidden pt-32 pb-24 sm:pt-40">
        <div class="grid-bg absolute inset-0 -z-10 [mask-image:radial-gradient(ellipse_at_top,black_20%,transparent_70%)]"></div>
        <div data-parallax="-30" class="absolute -top-24 left-1/4 -z-10 h-80 w-80 rounded-full bg-volt-400/20 blur-[110px]"></div>
        <div data-parallax="-50" class="absolute top-40 right-0 -z-10 h-72 w-72 rounded-full bg-ember-500/15 blur-[110px]"></div>

        <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_28rem]">
            <div>
                <p data-intro class="text-sm font-semibold uppercase tracking-[0.25em] text-volt-400">Book a demo</p>
                <h1 data-split class="mt-5 text-4xl font-bold leading-[1.05] sm:text-6xl">See FlexFit run <span class="text-volt-400">your</span> gym.</h1>
                <p data-intro class="mt-6 max-w-xl text-lg text-slate-400">A 20-minute video call with our team. Bring your questions; we'll show you the parts that matter for your gym.</p>
                <ul class="mt-10 space-y-5" data-stagger>
                    @foreach ([
                        ['Your QR sign-up, live', 'We set up a test gym with your name and logo and scan it together.'],
                        ['A day in the owner panel', 'Members, fees, approvals, trainers and attendance.'],
                        ['AI body transformation', 'Baseline, monthly scans, before/after slider and reports.'],
                        ['Pricing for your size', 'Which plan fits, and how the free trial works.'],
                    ] as [$t, $d])
                        <li class="flex gap-4">
                            <span class="mt-1 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-volt-400 text-xs font-bold text-on-volt">✓</span>
                            <div><p class="font-semibold text-white">{{ $t }}</p><p class="text-sm text-slate-400">{{ $d }}</p></div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div data-intro>
                @if (session('status'))
                    <div class="card p-8 text-center" role="status">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-volt-400 text-2xl text-on-volt">✓</span>
                        <h2 class="mt-5 text-2xl font-bold">Request received</h2>
                        <p class="mt-2 text-slate-400">{{ session('status') }}</p>
                        <a href="{{ route('register') }}" class="btn-ghost mt-6">Or start your free trial now</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('demo.store') }}" class="card space-y-4 p-6 sm:p-8" novalidate>
                        @csrf
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div>
                            <label for="name" class="label">Your name</label>
                            <input id="name" name="name" value="{{ old('name') }}" class="input" autocomplete="name" required>
                            @error('name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="email" class="label">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required>
                                @error('email') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="label">Mobile</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="input" autocomplete="tel" required>
                                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="gym_name" class="label">Gym name</label>
                                <input id="gym_name" name="gym_name" value="{{ old('gym_name') }}" class="input" autocomplete="organization" required>
                                @error('gym_name') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="city" class="label">City</label>
                                <input id="city" name="city" value="{{ old('city') }}" class="input" autocomplete="address-level2">
                            </div>
                        </div>
                        <div>
                            <label for="members" class="label">Gym size</label>
                            <select id="members" name="members" class="input">
                                <option value="">Choose…</option>
                                @foreach (\App\Models\DemoRequest::SIZES as $v => $l)<option value="{{ $v }}" {{ old('members') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="preferred_date" class="label">Preferred date</label>
                                <input id="preferred_date" name="preferred_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('preferred_date') }}" class="input">
                                @error('preferred_date') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="preferred_time" class="label">Preferred time</label>
                                <select id="preferred_time" name="preferred_time" class="input">
                                    <option value="">Any time</option>
                                    @foreach (\App\Models\DemoRequest::TIMES as $v => $l)<option value="{{ $v }}" {{ old('preferred_time') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="message" class="label">Anything we should know? <span class="text-slate-500">(optional)</span></label>
                            <textarea id="message" name="message" rows="3" class="input">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full py-3 text-base">Book my demo</button>
                        <p class="text-center text-xs text-slate-500">We'll only use these details to arrange your demo.</p>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
