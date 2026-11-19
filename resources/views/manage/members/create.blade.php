<x-app-layout title="Add member">
    <x-slot name="actions">
        <a href="{{ route('manage.members.import') }}" class="btn-ghost">Import CSV</a>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 max-w-3xl rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300" role="alert">Please fix the highlighted fields below.</div>
    @endif

    @if ($enquiry)
        <div class="mb-6 max-w-3xl rounded-xl border border-volt-400/30 bg-volt-400/10 px-4 py-3 text-sm text-volt-200" role="status">
            Converting enquiry from <strong>{{ $enquiry->name }}</strong>. It will be marked as joined once you register them.
        </div>
    @endif

    <form method="POST" action="{{ route('manage.members.store') }}" class="max-w-3xl space-y-6" novalidate>
        @csrf
        @if ($enquiry)<input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">@endif

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Member details</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="label">Full name</label>
                    <input id="name" name="name" value="{{ old('name', optional($enquiry)->name) }}" class="input" autocomplete="off" required autofocus>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="label">Mobile number</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone', $enquiry ? substr($enquiry->phone, 3) : '') }}" placeholder="98200 00000" class="input" required>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', optional($enquiry)->email) }}" class="input" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="label">Date of birth <span class="text-slate-500">(optional)</span></label>
                    <input id="date_of_birth" name="date_of_birth" type="date" max="{{ now()->subYears(10)->toDateString() }}" value="{{ old('date_of_birth') }}" class="input">
                    @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="gender" class="label">Gender <span class="text-slate-500">(optional)</span></label>
                    <select id="gender" name="gender" class="input">
                        <option value="">Not specified</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)<option value="{{ $v }}" {{ old('gender') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="height_cm" class="label">Height (cm) <span class="text-slate-500">(optional)</span></label>
                    <input id="height_cm" name="height_cm" type="number" step="0.1" value="{{ old('height_cm') }}" class="input">
                    @error('height_cm') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="weight_kg" class="label">Weight (kg) <span class="text-slate-500">(optional)</span></label>
                    <input id="weight_kg" name="weight_kg" type="number" step="0.1" value="{{ old('weight_kg') }}" class="input">
                    @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fitness_goal" class="label">Fitness goal <span class="text-slate-500">(optional)</span></label>
                    <select id="fitness_goal" name="fitness_goal" class="input">
                        <option value="">Not set</option>
                        @foreach ($goals as $v => $l)<option value="{{ $v }}" {{ old('fitness_goal') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="emergency_contact_name" class="label">Emergency contact <span class="text-slate-500">(optional)</span></label>
                    <input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" placeholder="Name" class="input">
                </div>
                <div>
                    <label for="trainer_id" class="label">Assigned trainer <span class="text-slate-500">(optional)</span></label>
                    <select id="trainer_id" name="trainer_id" class="input">
                        <option value="">No trainer</option>
                        @foreach ($trainers as $t)<option value="{{ $t->id }}" {{ (string) old('trainer_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="emergency_contact_phone" class="label">Emergency contact mobile</label>
                    <input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" value="{{ old('emergency_contact_phone') }}" class="input">
                    @error('emergency_contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Membership <span class="text-sm font-normal text-slate-500">(optional)</span></h2>
            @if ($plans->isEmpty())
                <p class="text-sm text-slate-400">No membership plans are set up for your gym yet.</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-600 bg-ink-850 p-4 has-[:checked]:border-volt-400 has-[:checked]:bg-volt-400/10">
                        <input type="radio" name="membership_plan_id" value="" class="text-volt-500 focus:ring-volt-400" {{ old('membership_plan_id', '') === '' ? 'checked' : '' }}>
                        <span class="text-sm text-slate-300">No plan yet</span>
                    </label>
                    @foreach ($plans as $plan)
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-ink-600 bg-ink-850 p-4 has-[:checked]:border-volt-400 has-[:checked]:bg-volt-400/10">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="membership_plan_id" value="{{ $plan->id }}" class="text-volt-500 focus:ring-volt-400" {{ (string) old('membership_plan_id') === (string) $plan->id ? 'checked' : '' }}>
                                <span><span class="block text-sm font-semibold text-white">{{ $plan->name }}</span><span class="text-xs text-slate-500">{{ $plan->durationLabel() }}</span></span>
                            </span>
                            <span class="font-display font-bold text-white">₹{{ number_format((float) $plan->price) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('membership_plan_id') <p class="field-error">{{ $message }}</p> @enderror
                <fieldset>
                    <legend class="label">Payment</legend>
                    <div class="flex flex-wrap gap-4 text-sm text-slate-300">
                        <label class="flex items-center gap-2"><input type="radio" name="payment" value="paid" class="text-volt-500 focus:ring-volt-400" {{ old('payment', 'paid') === 'paid' ? 'checked' : '' }}> Paid at the desk — activate now</label>
                        <select name="payment_method" class="input w-auto py-1.5 text-sm" aria-label="Payment method">
                            @foreach (\App\Models\Payment::DESK_METHODS as $v => $l)<option value="{{ $v }}" {{ old('payment_method', 'cash') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                        </select>
                        <label class="flex items-center gap-2"><input type="radio" name="payment" value="pending" class="text-volt-500 focus:ring-volt-400" {{ old('payment') === 'pending' ? 'checked' : '' }}> Not paid yet — member pays in the app</label>
                    </div>
                    @error('payment') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
            @endif
        </section>

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Login <span class="text-sm font-normal text-slate-500">(optional)</span></h2>
            <p class="text-sm text-slate-400">Leave blank and the member sets their own password with “Forgot password” on the login page.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="new-password">
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('manage.users.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Register member</button>
        </div>
    </form>
</x-app-layout>
