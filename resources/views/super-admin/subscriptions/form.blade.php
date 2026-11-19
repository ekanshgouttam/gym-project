<x-app-layout :title="($subscription->exists ? 'Edit subscription' : 'Start subscription') . ' · ' . $gym->name">
    <form method="POST" action="{{ $subscription->exists ? route('super-admin.subscriptions.update', $subscription) : route('super-admin.subscriptions.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        @if ($subscription->exists) @method('PUT') @else <input type="hidden" name="gym_id" value="{{ $gym->id }}"> @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="subscription_plan_id" class="label">Plan</label>
                <select id="subscription_plan_id" name="subscription_plan_id" class="input" required>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" {{ (string) old('subscription_plan_id', $subscription->subscription_plan_id) === (string) $plan->id ? 'selected' : '' }}>{{ $plan->name }} — ₹{{ number_format($plan->price_monthly) }}/mo</option>
                    @endforeach
                </select>
                @error('subscription_plan_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="billing_cycle" class="label">Billing cycle</label>
                <select id="billing_cycle" name="billing_cycle" class="input">
                    @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $v => $l)
                        <option value="{{ $v }}" {{ old('billing_cycle', $subscription->billing_cycle) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    @foreach (\App\Models\GymSubscription::STATUSES as $v => $l)
                        <option value="{{ $v }}" {{ old('status', $subscription->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('status') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="amount" class="label">Amount per cycle (₹) <span class="text-slate-500">(blank = plan price)</span></label>
                <input id="amount" name="amount" type="number" min="0" value="{{ old('amount', $subscription->exists ? $subscription->amount : '') }}" class="input">
                @error('amount') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="starts_on" class="label">Starts on</label>
                <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', optional($subscription->starts_on)->toDateString()) }}" class="input" required>
                @error('starts_on') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ends_on" class="label">Ends on</label>
                <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', optional($subscription->ends_on)->toDateString()) }}" class="input">
                @error('ends_on') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-slate-500">Tip: recording a paid payment activates the subscription and extends it by one billing cycle automatically.</p>

        <div class="flex items-center justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('super-admin.subscriptions.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">{{ $subscription->exists ? 'Save changes' : 'Start subscription' }}</button>
        </div>
    </form>
</x-app-layout>
