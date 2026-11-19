<x-guest-layout title="Reset password">
    <h1 class="text-3xl font-bold" data-intro>Forgot your password?</h1>
    <p class="mt-2 text-slate-400" data-intro>Enter your email and we'll send you a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5" data-intro novalidate>
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required autofocus>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary w-full">Send reset link</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-400"><a href="{{ route('login') }}" class="text-volt-400 hover:text-volt-300">Back to sign in</a></p>
</x-guest-layout>
