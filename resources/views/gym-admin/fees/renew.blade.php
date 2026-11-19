<x-app-layout :title="'Collect fee · ' . $member->name">
    <div class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('gym-admin.fees.renew.store', $member) }}" class="card space-y-5 p-6" data-intro novalidate>
            @csrf
            <h2 class="text-base font-semibold">Choose a plan</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($plans as $plan)
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-ink-600 bg-ink-850 p-4 has-[:checked]:border-volt-400 has-[:checked]:bg-volt-400/10">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="membership_plan_id" value="{{ $plan->id }}" data-price="{{ (int) $plan->price }}" class="text-volt-500 focus:ring-volt-400" {{ (string) old('membership_plan_id', optional(optional($last)->plan)->id) === (string) $plan->id ? 'checked' : '' }} required>
                            <span><span class="block text-sm font-semibold text-white">{{ $plan->name }}</span><span class="text-xs text-slate-500">{{ $plan->durationLabel() }}</span></span>
                        </span>
                        <span class="font-display font-bold text-white">₹{{ number_format((float) $plan->price) }}</span>
                    </label>
                @endforeach
            </div>
            @error('membership_plan_id')<p class="field-error">{{ $message }}</p>@enderror
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="method" class="label">Paid by</label>
                    <select id="method" name="method" class="input">@foreach (\App\Models\Payment::DESK_METHODS as $v => $l)<option value="{{ $v }}" {{ old('method', 'cash') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="amount" class="label">Amount received (₹)</label>
                    <input id="amount" name="amount" type="number" min="0" step="1" value="{{ old('amount') }}" class="input" placeholder="Plan price">
                    <p class="mt-1 text-xs text-slate-500">Change only for a discount.</p>
                    @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2"><label for="note" class="label">Note / reference <span class="text-slate-500">(optional)</span></label><input id="note" name="note" value="{{ old('note') }}" class="input" placeholder="e.g. UPI ref 4321, festival discount"></div>
            </div>
            <div class="flex justify-end gap-3"><a href="{{ route('gym-admin.fees.index') }}" class="btn-ghost">Cancel</a><button type="submit" class="btn-primary">Record payment & activate</button></div>
        </form>

        <aside class="card h-fit space-y-3 p-6 text-sm" data-intro>
            <h2 class="text-base font-semibold">{{ $member->name }}</h2>
            <p class="text-slate-400">{{ optional($member->memberProfile)->member_code }} · {{ $member->phone }}</p>
            @if ($current)
                <p class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-emerald-200">Active: {{ $current->plan->name }} until {{ $current->ends_on->format('d M Y') }}. The new period starts the next day.</p>
            @elseif ($last)
                <p class="rounded-xl border border-ember-500/30 bg-ember-500/10 p-3 text-ember-200">Last membership ({{ $last->plan->name }}) ended {{ $last->ends_on->format('d M Y') }}. The new one starts today.</p>
            @else
                <p class="text-slate-400">No membership yet. It starts today.</p>
            @endif
        </aside>
    </div>
    <script>
        document.querySelectorAll('input[name=membership_plan_id]').forEach((r) => r.addEventListener('change', () => { document.getElementById('amount').placeholder = '₹' + Number(r.dataset.price).toLocaleString('en-IN'); }));
    </script>
</x-app-layout>
