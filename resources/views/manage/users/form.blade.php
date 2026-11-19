<x-app-layout :title="$user->exists ? 'Edit ' . $user->name : 'Add user'">
    <form method="POST" action="{{ $user->exists ? route('manage.users.update', $user) : route('manage.users.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Full name</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input" required>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="label">Phone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" class="input">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($editingSelf)
                <p class="rounded-xl border border-ink-700 bg-ink-850 px-4 py-3 text-sm text-slate-400 sm:col-span-2">You can't change your own role or status.</p>
            @else
                <div>
                    <label for="role" class="label">Role</label>
                    <select id="role" name="role" class="input" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role', $user->primaryRole() ?? request('role', \App\Support\Roles::MEMBER)) === $role ? 'selected' : '' }}>{{ \App\Support\Roles::label($role) }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" name="status" class="input" required>
                        @foreach (['active' => 'Active', 'suspended' => 'Suspended'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $user->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            @if (auth()->user()->isSuperAdmin())
                <div class="sm:col-span-2">
                    <label for="gym_id" class="label">Gym <span class="text-slate-500">(not used for Super Admins)</span></label>
                    <select id="gym_id" name="gym_id" class="input">
                        <option value="">No gym</option>
                        @foreach ($gyms as $gym)
                            <option value="{{ $gym->id }}" {{ (string) old('gym_id', $user->gym_id) === (string) $gym->id ? 'selected' : '' }}>{{ $gym->name }}</option>
                        @endforeach
                    </select>
                    @error('gym_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label for="password" class="label">{{ $user->exists ? 'New password' : 'Password' }}</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                @if ($user->exists)<p class="mt-1.5 text-xs text-slate-500">Leave blank to keep the current password.</p>@endif
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('manage.users.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">{{ $user->exists ? 'Save changes' : 'Create user' }}</button>
        </div>
    </form>
</x-app-layout>
