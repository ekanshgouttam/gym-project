<x-app-layout title="My Profile">
    <div class="grid gap-6 xl:grid-cols-3">
        {{-- ID card --}}
        <aside class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-volt-400/30 bg-gradient-to-br from-ink-800 to-ink-900 p-6" data-intro>
                <div class="grid-bg absolute inset-0 opacity-40"></div>
                <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-volt-400/20 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-center justify-between"><x-logo class="text-sm" /><span class="text-xs uppercase tracking-widest text-slate-400">Member</span></div>
                    <div class="mt-6 flex items-center gap-4">
                        @if ($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="" class="h-20 w-20 rounded-2xl object-cover">
                        @else
                            <span class="grid h-20 w-20 place-items-center rounded-2xl bg-ink-700 font-display text-2xl font-bold">{{ $user->initials() }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-lg font-semibold">{{ $user->name }}</p>
                            <p class="font-mono text-sm text-volt-300">{{ $profile->member_code ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ optional($user->gym)->name }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('member.profile.photo') }}" enctype="multipart/form-data" class="card space-y-4 p-6" data-busy="Uploading…">
                @csrf
                <h2 class="text-base font-semibold">Profile photo</h2>
                <input name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white" required>
                @error('photo') <p class="field-error">{{ $message }}</p> @enderror
                <button class="btn-ghost w-full">Upload new photo</button>
            </form>

            <section class="card p-6">
                <h2 class="text-base font-semibold">Contact</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Mobile</dt><dd class="text-slate-200">{{ $user->phone ?: '—' }} @if($profile->phone_verified_at)<span class="badge ml-1 bg-emerald-500/15 text-emerald-300">Verified</span>@endif</dd></div>
                    <div><dt class="text-slate-500">Email</dt><dd class="text-slate-200">{{ $user->email }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-slate-500">To change your mobile number, please visit the front desk. Email can be changed in <a href="{{ route('member.settings') }}" class="text-volt-400">Account Settings</a>.</p>
            </section>
        </aside>

        {{-- Editable details --}}
        <form method="POST" action="{{ route('member.profile.update') }}" class="card space-y-6 p-6 xl:col-span-2" novalidate>
            @csrf @method('PUT')
            <h2 class="text-base font-semibold">Personal details</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="label">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="label">Date of birth</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', optional($profile->date_of_birth)->toDateString()) }}" class="input">
                    @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="gender" class="label">Gender</label>
                    <select id="gender" name="gender" class="input">
                        <option value="">Prefer not to say</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" {{ old('gender', $profile->gender) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="height_cm" class="label">Height (cm)</label>
                    <input id="height_cm" name="height_cm" type="number" step="0.1" value="{{ old('height_cm', $profile->height_cm) }}" class="input">
                    @error('height_cm') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="target_weight_kg" class="label">Target weight (kg)</label>
                    <input id="target_weight_kg" name="target_weight_kg" type="number" step="0.1" value="{{ old('target_weight_kg', $profile->target_weight_kg) }}" class="input">
                    @error('target_weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="fitness_goal" class="label">Fitness goal</label>
                    <select id="fitness_goal" name="fitness_goal" class="input">
                        @foreach ($goals as $v => $l)
                            <option value="{{ $v }}" {{ old('fitness_goal', $profile->fitness_goal) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h2 class="border-t border-ink-700 pt-6 text-base font-semibold">Emergency contact</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="emergency_contact_name" class="label">Name</label>
                    <input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}" class="input">
                </div>
                <div>
                    <label for="emergency_contact_phone" class="label">Mobile</label>
                    <input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" value="{{ old('emergency_contact_phone', $profile->emergency_contact_phone) }}" class="input">
                    @error('emergency_contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end border-t border-ink-700 pt-6">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</x-app-layout>
