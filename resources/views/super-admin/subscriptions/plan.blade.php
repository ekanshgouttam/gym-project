<x-app-layout :title="$plan->exists ? 'Edit plan · ' . $plan->name : 'New subscription plan'">
    <form method="POST" action="{{ $plan->exists ? route('super-admin.plans.update', $plan) : route('super-admin.plans.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        @if ($plan->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Plan name</label>
                <input id="name" name="name" value="{{ old('name', $plan->name) }}" class="input" required>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="price_monthly" class="label">Monthly price (₹)</label>
                <input id="price_monthly" name="price_monthly" type="number" min="0" value="{{ old('price_monthly', $plan->price_monthly) }}" class="input" required>
                @error('price_monthly') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="price_yearly" class="label">Yearly price (₹) <span class="text-slate-500">(optional)</span></label>
                <input id="price_yearly" name="price_yearly" type="number" min="0" value="{{ old('price_yearly', $plan->price_yearly) }}" class="input">
                @error('price_yearly') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="member_limit" class="label">Member limit <span class="text-slate-500">(blank = unlimited)</span></label>
                <input id="member_limit" name="member_limit" type="number" min="1" value="{{ old('member_limit', $plan->member_limit) }}" class="input">
                @error('member_limit') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ai_scans_per_month" class="label">AI scans per month <span class="text-slate-500">(blank = unlimited)</span></label>
                <input id="ai_scans_per_month" name="ai_scans_per_month" type="number" min="0" value="{{ old('ai_scans_per_month', $plan->ai_scans_per_month) }}" class="input">
                @error('ai_scans_per_month') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="features" class="label">Features <span class="text-slate-500">(one per line)</span></label>
                <textarea id="features" name="features" rows="4" class="input">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea>
                @error('features') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="sort_order" class="label">Display order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}" class="input">
            </div>
            <label class="flex items-center gap-2 self-end pb-3 text-sm text-slate-300">
                <input type="checkbox" name="is_active" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                On sale to new gyms
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('super-admin.subscriptions.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">{{ $plan->exists ? 'Save plan' : 'Create plan' }}</button>
        </div>
    </form>
</x-app-layout>
