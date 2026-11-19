<x-app-layout title="Account Settings">
    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ route('member.settings.preferences') }}" class="card p-6">
            @csrf @method('PUT')
            <h2 class="text-base font-semibold">Notifications</h2>
            <p class="mt-1 text-sm text-slate-400">Choose which in-app notifications you receive. Membership activation notices are always sent.</p>
            <div class="mt-5 divide-y divide-ink-700">
                @foreach ([
                    'membership' => ['Membership reminders', 'Renewal and expiry reminders'],
                    'scans' => ['AI scan results', 'When a body scan report is ready'],
                    'plans' => ['Workout & diet plans', 'When your trainer assigns or updates a plan'],
                    'promotions' => ['Offers & events', 'Occasional offers and gym events'],
                ] as $key => [$label, $hint])
                    <label class="flex cursor-pointer items-center justify-between gap-4 py-3">
                        <span><span class="block text-sm font-medium text-white">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $hint }}</span></span>
                        <input type="checkbox" name="prefs[{{ $key }}]" value="1" class="h-5 w-5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ ! empty($prefs[$key]) ? 'checked' : '' }}>
                    </label>
                @endforeach
            </div>
            <div class="mt-4 flex justify-end"><button class="btn-primary">Save preferences</button></div>
        </form>

        <form method="POST" action="{{ route('member.settings.email') }}" class="card space-y-5 p-6" novalidate>
            @csrf @method('PUT')
            <h2 class="text-base font-semibold">Email address</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input" autocomplete="email" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email_current_password" class="label">Current password</label>
                    <input id="email_current_password" name="current_password" type="password" class="input" autocomplete="current-password" required>
                    @if (old('email')) @error('current_password') <p class="field-error">{{ $message }}</p> @enderror @endif
                </div>
            </div>
            <div class="flex justify-end"><button class="btn-ghost">Update email</button></div>
        </form>

        <form method="POST" action="{{ route('member.settings.password') }}" class="card space-y-5 p-6" novalidate>
            @csrf @method('PUT')
            <h2 class="text-base font-semibold">Change password</h2>
            <div>
                <label for="current_password" class="label">Current password</label>
                <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required>
                @unless (old('email')) @error('current_password') <p class="field-error">{{ $message }}</p> @enderror @endunless
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">New password</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="new-password" required>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
                </div>
            </div>
            <p class="text-xs text-slate-500">Changing your password signs you out on all other devices.</p>
            <div class="flex justify-end"><button class="btn-primary">Change password</button></div>
        </form>
    </div>
</x-app-layout>
