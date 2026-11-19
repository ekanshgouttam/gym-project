<x-public-layout title="Pricing">
    <x-page-hero eyebrow="Pricing" title="Pay for your gym,<br><span class='text-volt-400'>not</span> per member." :lead="($trialDays ? 'Every plan starts with a ' . $trialDays . '-day free trial. ' : '') . 'Members use the app at no extra cost.'">
        <div data-billing-toggle class="mx-auto mt-10 inline-flex rounded-full border border-ink-700 bg-ink-900 p-1" role="group" aria-label="Billing period" data-intro>
            <button type="button" data-billing="monthly" class="rounded-full px-5 py-2 text-sm font-semibold transition">Monthly</button>
            <button type="button" data-billing="yearly" class="rounded-full px-5 py-2 text-sm font-semibold transition">Yearly <span class="text-xs opacity-70">2 months free</span></button>
        </div>
    </x-page-hero>

    <section class="pb-24">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 md:grid-cols-3" data-stagger>
            @forelse ($plans as $plan)
                <x-saas-plan-card :plan="$plan" :featured="$plans->count() > 2 && $loop->index === 1" :trial-days="$trialDays" />
            @empty
                <p class="text-center text-slate-500">Plans are being updated. <a href="{{ route('demo') }}" class="text-volt-400">Talk to us</a>.</p>
            @endforelse
        </div>
    </section>

    @if ($plans->isNotEmpty())
        <section class="border-t border-ink-700 py-24">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <h2 class="text-center text-3xl font-bold" data-reveal>Compare plans</h2>
                <div class="card mt-10 overflow-x-auto" data-reveal>
                    <table class="min-w-full divide-y divide-ink-700 text-sm">
                        <thead class="bg-ink-850/60">
                            <tr>
                                <th class="table-head">&nbsp;</th>
                                @foreach ($plans as $plan)<th class="table-head text-center text-white">{{ $plan->name }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            <tr><td class="table-cell text-slate-400">Members</td>@foreach ($plans as $p)<td class="table-cell text-center">{{ $p->member_limit ? number_format($p->member_limit) : 'Unlimited' }}</td>@endforeach</tr>
                            <tr><td class="table-cell text-slate-400">AI body scans / month</td>@foreach ($plans as $p)<td class="table-cell text-center">{{ $p->ai_scans_per_month !== null ? number_format($p->ai_scans_per_month) : 'Unlimited' }}</td>@endforeach</tr>
                            @foreach (['QR registration & OTP', 'Member app', 'Online payments', 'Trainer accounts'] as $row)
                                <tr><td class="table-cell text-slate-400">{{ $row }}</td>@foreach ($plans as $p)<td class="table-cell text-center text-volt-400">✓</td>@endforeach</tr>
                            @endforeach
                            <tr><td class="table-cell text-slate-400">Monthly price</td>@foreach ($plans as $p)<td class="table-cell text-center font-semibold text-white">₹{{ number_format($p->price_monthly) }}</td>@endforeach</tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-ink-700 py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold" data-reveal>Questions</h2>
            <div class="mt-10 space-y-3" data-stagger>
                @foreach ([
                    ['Do members pay anything to FlexFit?', 'No. Members pay their gym for membership. FlexFit charges the gym a flat subscription.'],
                    ['What happens after the free trial?', 'Pick a plan and pay by UPI or bank transfer. Our team will reach out before your trial ends, and your data stays safe either way.'],
                    ['Can I change plans later?', 'Yes, upgrade or downgrade any time. Changes apply from your next billing period.'],
                    ['What counts as an AI body scan?', 'Each AI report or before/after comparison a member runs. Uploads always work; reports are what count.'],
                    ['Are AI reports medical results?', 'No. They are visual observations from photos and never claim body-fat %, muscle mass or medical measurements.'],
                ] as [$q, $a])
                    <details class="card group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-white">
                            {{ $q }}
                            <span class="text-volt-400 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-slate-400">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
