<x-app-layout :title="$gym->exists ? 'Edit ' . $gym->name : 'New gym'">
    <form method="POST" action="{{ $gym->exists ? route('manage.gyms.update', $gym) : route('manage.gyms.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        @if ($gym->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Gym name</label>
                <input id="name" name="name" value="{{ old('name', $gym->name) }}" class="input" required>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $gym->email) }}" class="input">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="label">Phone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $gym->phone) }}" class="input">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="address" class="label">Address</label>
                <input id="address" name="address" value="{{ old('address', $gym->address) }}" class="input">
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="city" class="label">City</label>
                <input id="city" name="city" value="{{ old('city', $gym->city) }}" class="input">
                @error('city') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300 sm:col-span-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('is_active', $gym->is_active) ? 'checked' : '' }}>
                Active — visible on the public site and open for registration
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('manage.gyms.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">{{ $gym->exists ? 'Save changes' : 'Create gym' }}</button>
        </div>
    </form>
</x-app-layout>
