<x-guest-layout title="Sign in">
    <h1 class="text-3xl font-bold" data-intro>Welcome back</h1>
    <p class="mt-2 text-slate-400" data-intro>Sign in to your {{ config('app.name') }} account.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-intro novalidate>
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required autofocus>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Password</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-sm text-volt-400 hover:text-volt-300">Forgot password?</a>
            </div>
            <input id="password" name="password" type="password" class="input" autocomplete="current-password" required>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400">
            Keep me signed in
        </label>
        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-400">
        Own a gym? <a href="{{ route('register') }}" class="font-semibold text-volt-400 hover:text-volt-300">Register your gym</a>
        <span class="mx-1 text-slate-600">·</span>
        Member? <a href="{{ route('join') }}" class="font-semibold text-volt-400 hover:text-volt-300">Join your gym</a>
    </p>
</x-guest-layout>
