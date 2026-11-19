<x-guest-layout title="Register your gym">
    <p class="text-sm font-semibold uppercase tracking-widest text-volt-400" data-intro>For gym owners</p>
    <h1 class="mt-2 text-3xl font-bold" data-intro>Register your gym</h1>
    <p class="mt-2 text-slate-400" data-intro>Get your own gym dashboard and member QR code in two minutes.@if ($open && $trialDays) Free for {{ $trialDays }} days.@endif</p>

    @if (! $open)
        <div class="mt-8 rounded-xl border border-ember-500/30 bg-ember-500/10 px-4 py-3 text-sm text-ember-300" role="status">
            New gym registrations are paused right now. Please check back soon or contact us.
        </div>
    @else
    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" role="alert" data-error-summary>
            <p class="font-semibold">Please fix {{ $errors->count() === 1 ? 'this' : 'these' }} and submit again:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
            <p class="mt-2 text-xs text-rose-300/80">If you chose a logo, choose it again — browsers clear file fields after an error.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-8 space-y-6" data-intro novalidate>
        @csrf

        <fieldset class="space-y-5">
            <legend class="mb-4 flex items-center gap-3 text-sm font-semibold text-white"><span class="grid h-6 w-6 place-items-center rounded-full bg-volt-400 text-xs text-ink-950">1</span> Your gym</legend>
            <div>
                <label for="gym_name" class="label">Gym name</label>
                <input id="gym_name" name="gym_name" value="{{ old('gym_name') }}" class="input" autocomplete="organization" placeholder="e.g. Gold Gym Andheri" required autofocus>
                @error('gym_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="address" class="label">Gym address</label>
                <input id="address" name="address" value="{{ old('address') }}" class="input" autocomplete="street-address" placeholder="Building, street, area" required>
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="city" class="label">City</label>
                <input id="city" name="city" value="{{ old('city') }}" class="input" autocomplete="address-level2" required>
                @error('city') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-4">
                <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl border border-ink-600 bg-ink-850">
                    <span data-preview-placeholder class="absolute inset-0 grid place-items-center text-[11px] text-slate-500">Logo</span>
                    <img id="logo-preview" alt="" class="hidden h-full w-full object-contain p-1">
                </div>
                <div class="min-w-0 flex-1">
                    <label for="logo" class="label">Gym logo <span class="text-slate-500">(optional)</span></label>
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#logo-preview" data-max-mb="10" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, WebP or GIF, up to 10 MB.</p>
                    @error('logo') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-5 border-t border-ink-700 pt-6">
            <legend class="mb-4 flex items-center gap-3 text-sm font-semibold text-white"><span class="grid h-6 w-6 place-items-center rounded-full bg-volt-400 text-xs text-ink-950">2</span> Owner account</legend>
            <div>
                <label for="name" class="label">Owner name</label>
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
                    <label for="phone" class="label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}" placeholder="98200 00000" class="input" autocomplete="tel-national" required>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="new-password" required>
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
                </div>
            </div>
            @error('password') <p class="field-error -mt-3">{{ $message }}</p> @enderror
            <p class="-mt-3 text-xs text-slate-500">At least 8 characters, with letters and numbers.</p>
        </fieldset>

        <label class="flex items-start gap-2 text-sm text-slate-400">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('terms') ? 'checked' : '' }} required>
            I agree to the {{ config('app.name') }} terms of service.
        </label>
        @error('terms') <p class="field-error">{{ $message }}</p> @enderror
        <button type="submit" class="btn-primary w-full py-3 text-base">Create my gym</button>
    </form>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500">
        Want to join a gym as a member? <a href="{{ route('join') }}" class="text-volt-400 hover:text-volt-300">Find your gym</a>
    </p>
    <p class="mt-3 text-center text-sm text-slate-400">
        Already registered? <a href="{{ route('login') }}" class="font-semibold text-volt-400 hover:text-volt-300">Sign in</a>
    </p>
</x-guest-layout>
