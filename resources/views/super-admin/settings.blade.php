<x-app-layout title="Platform settings">
    <form method="POST" action="{{ route('super-admin.settings.update') }}" class="max-w-3xl space-y-6" novalidate>
        @csrf @method('PUT')

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Gym sign-ups</h2>
            <label class="flex items-start gap-3 text-sm text-slate-300">
                <input type="checkbox" name="allow_gym_registration" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('allow_gym_registration', $settings['allow_gym_registration']) ? 'checked' : '' }}>
                <span>Allow new gyms to register themselves at <span class="font-mono text-xs text-slate-400">{{ route('register') }}</span></span>
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="trial_days" class="label">Free trial (days)</label>
                    <input id="trial_days" name="trial_days" type="number" min="0" max="365" value="{{ old('trial_days', $settings['trial_days']) }}" class="input">
                    @error('trial_days') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="default_plan" class="label">Plan new gyms start on</label>
                    <select id="default_plan" name="default_plan" class="input">
                        @foreach ($plans as $plan)<option value="{{ $plan->slug }}" {{ old('default_plan', $settings['default_plan']) === $plan->slug ? 'selected' : '' }}>{{ $plan->name }}</option>@endforeach
                    </select>
                    @error('default_plan') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="support_email" class="label">Support email shown to gym owners</label>
                    <input id="support_email" name="support_email" type="email" value="{{ old('support_email', $settings['support_email']) }}" class="input" placeholder="support@yourdomain.com">
                    @error('support_email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">AI body scans</h2>
            <p class="text-sm text-slate-400">
                API key: <span class="{{ $keySet ? 'text-emerald-300' : 'text-ember-300' }}">{{ $keySet ? 'set' : 'missing — add ANTHROPIC_API_KEY to .env' }}</span> · model <span class="font-mono text-xs">{{ $model }}</span>
            </p>
            <label class="flex items-start gap-3 text-sm text-slate-300">
                <input type="checkbox" name="ai_scans_enabled" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('ai_scans_enabled', $settings['ai_scans_enabled']) ? 'checked' : '' }}>
                <span>Run AI analysis for body scans and comparisons. When off, members can still upload; reports run later when switched back on.</span>
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="ai_monthly_budget_usd" class="label">Monthly AI budget (USD)</label>
                    <input id="ai_monthly_budget_usd" name="ai_monthly_budget_usd" type="number" min="0" step="0.01" value="{{ old('ai_monthly_budget_usd', $settings['ai_monthly_budget_usd']) }}" class="input">
                    <p class="mt-1 text-xs text-slate-500">Used for the budget bar in AI usage. 0 hides it.</p>
                    @error('ai_monthly_budget_usd') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="usd_to_inr" class="label">USD → INR rate</label>
                    <input id="usd_to_inr" name="usd_to_inr" type="number" min="1" step="0.01" value="{{ old('usd_to_inr', $settings['usd_to_inr']) }}" class="input">
                    @error('usd_to_inr') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>
</x-app-layout>
