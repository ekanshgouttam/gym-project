<x-guest-layout title="Choose a new password">
    <h1 class="text-3xl font-bold" data-intro>Choose a new password</h1>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" data-intro novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="input" autocomplete="email" required>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="label">New password</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required autofocus>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-primary w-full">Reset password</button>
    </form>
</x-guest-layout>
