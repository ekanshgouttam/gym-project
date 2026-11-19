<x-app-layout :title="$plan->exists ? 'Edit plan · ' . $plan->name : 'New membership plan'">
    <form method="POST" action="{{ $plan->exists ? route('gym-admin.fees.plans.update', $plan) : route('gym-admin.fees.plans.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        @if ($plan->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="name" class="label">Plan name</label><input id="name" name="name" value="{{ old('name', $plan->name) }}" class="input" placeholder="e.g. Quarterly" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div>
                <label for="duration_months" class="label">Duration</label>
                <select id="duration_months" name="duration_months" class="input">
                    @foreach ([1 => '1 month', 2 => '2 months', 3 => '3 months', 6 => '6 months', 9 => '9 months', 12 => '1 year', 24 => '2 years'] as $v => $l)
                        <option value="{{ $v }}" {{ (int) old('duration_months', $plan->duration_months) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="price" class="label">Price (₹)</label><input id="price" name="price" type="number" min="0" step="1" value="{{ old('price', $plan->exists ? (int) $plan->price : '') }}" class="input" required>@error('price')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label for="description" class="label">Short description <span class="text-slate-500">(optional)</span></label><input id="description" name="description" value="{{ old('description', $plan->description) }}" class="input"></div>
            <div class="sm:col-span-2"><label for="features" class="label">What's included <span class="text-slate-500">(one per line)</span></label><textarea id="features" name="features" rows="4" class="input" placeholder="Gym floor access&#10;1 personal training session&#10;Monthly AI body scan">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea></div>
            <div><label for="sort_order" class="label">Display order</label><input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}" class="input"></div>
            <div class="flex flex-col justify-end gap-2 pb-2 text-sm text-slate-300">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}> On sale (shown on join page)</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}> Mark as popular</label>
            </div>
        </div>
        <p class="text-xs text-slate-500">Price changes apply to new memberships and renewals; existing ones keep what was paid.</p>
        <div class="flex justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('gym-admin.fees.index', ['tab' => 'plans']) }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">{{ $plan->exists ? 'Save plan' : 'Create plan' }}</button>
        </div>
    </form>
</x-app-layout>
