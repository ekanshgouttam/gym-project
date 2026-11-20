<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Join ' . $gym->name])
</head>
<body>
<div class="relative min-h-screen overflow-hidden">
    <div class="grid-bg absolute inset-0 -z-10 [mask-image:linear-gradient(to_bottom,black,transparent_60%)]"></div>
    <div class="absolute -top-32 left-1/2 -z-10 h-80 w-80 -translate-x-1/2 rounded-full bg-volt-400/20 blur-[100px]"></div>

    <main class="mx-auto max-w-xl px-4 pb-16 pt-8 sm:pt-12">
        <x-logo />
        <p data-intro class="mt-8 text-sm font-semibold uppercase tracking-widest text-volt-400">Member registration</p>
        <div data-intro class="mt-2 flex items-center gap-4">
            @if ($gym->logoUrl())<img src="{{ $gym->logoUrl() }}" alt="{{ $gym->name }} logo" class="h-14 w-14 shrink-0 rounded-2xl border border-ink-600 bg-ink-850 object-contain p-1">@endif
            <h1 class="text-3xl font-bold sm:text-4xl">Join {{ $gym->name }}</h1>
        </div>
        <p data-intro class="mt-2 text-slate-400">{{ collect([$gym->address, $gym->city])->filter()->implode(', ') }}</p>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" role="alert">
                Please fix the highlighted fields below.
            </div>
        @endif

        <form method="POST" action="{{ route('join.store', $gym->join_token) }}" enctype="multipart/form-data" class="mt-8 space-y-8" novalidate
              data-join data-send-url="{{ route('join.otp.send', $gym->join_token) }}" data-verify-url="{{ route('join.otp.verify', $gym->join_token) }}"
              data-verified="{{ $verifiedPhone ? '1' : '0' }}" data-busy="Creating your account…">
            @csrf

            {{-- Step 1: mobile + OTP --}}
            <section class="card p-5 sm:p-6" data-intro>
                <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">1</span> Verify your mobile</h2>
                <div class="mt-5">
                    <label for="phone" class="label">Mobile number</label>
                    <div class="flex gap-2">
                        <span class="input w-auto shrink-0 select-none text-slate-400">+91</span>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" value="{{ $verifiedPhone ? substr($verifiedPhone, 3) : old('phone') }}" placeholder="98200 00000" class="input" required>
                    </div>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="button" class="btn-primary mt-4 w-full" data-otp-send>Send OTP</button>
                <div class="mt-4 hidden" data-otp-code>
                    <label for="otp" class="label">6-digit code</label>
                    <div class="flex gap-2">
                        <input id="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="input text-center font-display text-xl tracking-[0.5em]">
                        <button type="button" class="btn-ghost shrink-0" data-otp-verify>Verify</button>
                    </div>
                </div>
                <p data-otp-msg class="mt-2 text-sm" aria-live="polite"></p>
                <p data-otp-verified class="mt-3 hidden items-center gap-2 text-sm font-medium text-volt-300">✓ Mobile number verified</p>
            </section>

            <div data-join-details class="hidden space-y-8">
                {{-- Step 2: About you --}}
                <section class="card space-y-5 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">2</span> About you</h2>

                    <div class="flex items-center gap-4">
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border border-ink-600 bg-ink-850">
                            <span data-preview-placeholder class="absolute inset-0 grid place-items-center text-xs text-slate-500">Photo</span>
                            <img id="photo-preview" alt="" class="hidden h-full w-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <label for="photo" class="label">Profile photo</label>
                            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="user" data-preview="#photo-preview" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white" required disabled>
                            @error('photo') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="name" class="label">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="input" autocomplete="name" required disabled>
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required disabled>
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="date_of_birth" class="label">Date of birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" max="{{ now()->subYears(12)->toDateString() }}" class="input" autocomplete="bday" required disabled>
                            @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="gender" class="label">Gender <span class="text-slate-500">(optional)</span></label>
                            <select id="gender" name="gender" class="input" disabled>
                                <option value="">Prefer not to say</option>
                                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('gender') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="height_cm" class="label">Height (cm)</label>
                            <input id="height_cm" name="height_cm" type="number" inputmode="decimal" step="0.1" min="100" max="250" value="{{ old('height_cm') }}" class="input" required disabled>
                            @error('height_cm') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="weight_kg" class="label">Weight (kg)</label>
                            <input id="weight_kg" name="weight_kg" type="number" inputmode="decimal" step="0.1" min="25" max="300" value="{{ old('weight_kg') }}" class="input" required disabled>
                            @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <fieldset>
                        <legend class="label">Fitness goal</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($goals as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-ink-600 bg-ink-850 px-3 py-2.5 text-sm has-[:checked]:border-volt-400 has-[:checked]:bg-volt-400/10">
                                    <input type="radio" name="fitness_goal" value="{{ $value }}" class="text-volt-500 focus:ring-volt-400" {{ old('fitness_goal') === $value ? 'checked' : '' }} required disabled>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('fitness_goal') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>
                </section>

                {{-- Step 3: Emergency contact --}}
                <section class="card space-y-5 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">3</span> Emergency contact</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="emergency_contact_name" class="label">Name</label>
                            <input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="input" required disabled>
                            @error('emergency_contact_name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="emergency_contact_phone" class="label">Mobile</label>
                            <input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" inputmode="numeric" value="{{ old('emergency_contact_phone') }}" class="input" required disabled>
                            @error('emergency_contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Step 4: Baseline body scan --}}
                <section class="card space-y-5 p-5 sm:p-6">
                    <div>
                        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">4</span> Baseline body scan</h2>
                        <p class="mt-2 text-sm text-slate-400">Three photos set the starting point for your transformation. Every monthly scan is compared against them. Your height and weight above are saved with this scan.</p>
                    </div>
                    @include('partials.scan-photo-inputs', ['disabled' => true])
                    <details class="rounded-xl border border-ink-700 bg-ink-850 p-4">
                        <summary class="cursor-pointer text-sm font-medium text-volt-300">How to take your baseline photos</summary>
                        @include('partials.scan-guide', ['class' => 'mt-3'])
                    </details>
                    <label class="flex items-start gap-3 text-sm text-slate-400">
                        <input type="checkbox" name="scan_consent" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('scan_consent') ? 'checked' : '' }} required disabled>
                        I agree to these photos being analysed by AI. They are stored privately and only visible to me and my gym's staff. AI reports are visual observations, not medical measurements.
                    </label>
                    @error('scan_consent') <p class="field-error">{{ $message }}</p> @enderror
                </section>

                {{-- Step 5: Plan --}}
                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">5</span> Membership plan</h2>
                    @foreach ($plans as $plan)
                        <label data-plan-card class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-ink-600 bg-ink-850 p-4">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="membership_plan_id" value="{{ $plan->id }}" class="text-volt-500 focus:ring-volt-400" {{ (string) old('membership_plan_id') === (string) $plan->id ? 'checked' : '' }} required disabled>
                                <span>
                                    <span class="block font-semibold text-white">{{ $plan->name }} @if($plan->is_featured)<span class="badge ml-1 bg-volt-400/15 text-volt-300">Popular</span>@endif</span>
                                    <span class="block text-xs text-slate-400">{{ $plan->durationLabel() }}</span>
                                </span>
                            </span>
                            <span class="text-right">
                                <span class="block font-display text-xl font-bold text-white">₹{{ number_format((float) $plan->price) }}</span>
                                @if ($plan->duration_months > 1)<span class="block text-xs text-slate-500">₹{{ number_format($plan->monthlyPrice()) }}/mo</span>@endif
                            </span>
                        </label>
                    @endforeach
                    @error('membership_plan_id') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-500">After registering you can pay online to activate instantly, or pay at the front desk and staff will approve your membership.</p>
                </section>

                {{-- Step 6: Account --}}
                <section class="card space-y-5 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-volt-400 text-sm text-on-volt">6</span> Create a password</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="label">Password</label>
                            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required disabled>
                            @error('password') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="label">Confirm</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required disabled>
                        </div>
                    </div>
                    <label class="flex items-start gap-3 text-sm text-slate-400">
                        <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('terms') ? 'checked' : '' }} required disabled>
                        I confirm my details are accurate and agree to the gym's membership terms.
                    </label>
                    @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                    <button type="submit" class="btn-primary w-full py-3 text-base" disabled>Complete registration</button>
                </section>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-slate-500">Already a member? <a href="{{ route('login') }}" class="text-volt-400 hover:text-volt-300">Log in</a></p>
    </main>
</div>
</body>
</html>
