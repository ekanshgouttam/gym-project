<x-app-layout title="Record subscription payment">
    @if ($gyms->isEmpty())
        <x-empty-state title="No subscriptions yet" body="Start a subscription for a gym first, then record its payments here.">
            <a href="{{ route('super-admin.subscriptions.index') }}" class="btn-primary">Go to subscriptions</a>
        </x-empty-state>
    @else
    <form method="POST" action="{{ route('super-admin.payments.store') }}" class="card max-w-3xl space-y-6 p-6" novalidate>
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="gym_id" class="label">Gym</label>
                <select id="gym_id" name="gym_id" class="input" required>
                    <option value="">Choose a gym</option>
                    @foreach ($gyms as $g)
                        <option value="{{ $g->id }}" data-amount="{{ $g->subscription->amount }}" {{ (string) old('gym_id', $selected) === (string) $g->id ? 'selected' : '' }}>
                            {{ $g->name }} — {{ $g->subscription->plan->name }}, {{ $g->subscription->billing_cycle }} ₹{{ number_format($g->subscription->amount) }} ({{ $g->subscription->statusLabel() }})
                        </option>
                    @endforeach
                </select>
                @error('gym_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="amount" class="label">Amount (₹)</label>
                <input id="amount" name="amount" type="number" min="1" value="{{ old('amount', optional(optional($gyms->firstWhere('id', $selected))->subscription)->amount) }}" class="input" required>
                @error('amount') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="paid_at" class="label">Payment date</label>
                <input id="paid_at" name="paid_at" type="date" max="{{ today()->toDateString() }}" value="{{ old('paid_at', today()->toDateString()) }}" class="input" required>
                @error('paid_at') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="method" class="label">Method</label>
                <select id="method" name="method" class="input">
                    @foreach (\App\Models\SubscriptionPayment::METHODS as $v => $l)<option value="{{ $v }}" {{ old('method', 'upi') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    @foreach (\App\Models\SubscriptionPayment::STATUSES as $v => $l)<option value="{{ $v }}" {{ old('status', 'paid') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="reference" class="label">Reference / UTR <span class="text-slate-500">(optional)</span></label>
                <input id="reference" name="reference" value="{{ old('reference') }}" class="input">
            </div>
            <div>
                <label for="notes" class="label">Notes <span class="text-slate-500">(optional)</span></label>
                <input id="notes" name="notes" value="{{ old('notes') }}" class="input">
            </div>
        </div>
        <p class="text-xs text-slate-500">A <strong class="text-slate-300">paid</strong> payment marks the subscription active and extends it by one billing cycle from its current end date (or today, if it has lapsed).</p>
        <div class="flex items-center justify-end gap-3 border-t border-ink-700 pt-6">
            <a href="{{ route('super-admin.payments.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Record payment</button>
        </div>
    </form>
    <script>
        document.getElementById('gym_id').addEventListener('change', (e) => {
            const amount = e.target.selectedOptions[0]?.dataset.amount;
            if (amount) document.getElementById('amount').value = amount;
        });
    </script>
    @endif
</x-app-layout>
