@props(['plan', 'featured' => false, 'trialDays' => 0])
{{-- FlexFit subscription plan. Prices switch with a [data-billing-toggle] on the page. --}}
@php
    $yearly = $plan->priceFor('yearly');
    $saving = $plan->price_monthly > 0 ? max(0, (int) round(100 - $yearly / ($plan->price_monthly * 12) * 100)) : 0;
@endphp
<article data-tilt class="group relative flex flex-col overflow-hidden rounded-3xl border p-7 transition-colors {{ $featured ? 'border-volt-400/60 bg-ink-850 shadow-glow' : 'border-white/[.06] bg-ink-900/75 backdrop-blur-xl hover:border-ink-600' }}">
    <div class="pointer-events-none absolute inset-0 opacity-0 transition group-hover:opacity-100" style="background: radial-gradient(400px circle at var(--mx, 50%) var(--my, 0%), rgb(var(--c-volt-400) / .12), transparent 40%);"></div>
    @if ($featured)
        <span class="badge absolute right-5 top-5 bg-volt-400 text-on-volt">Most popular</span>
    @endif
    <h3 class="text-2xl font-bold">{{ $plan->name }}</h3>
    <p class="mt-1 text-sm text-slate-400">{{ $plan->member_limit ? 'Up to ' . number_format($plan->member_limit) . ' members' : 'Unlimited members' }}</p>
    <p class="mt-6 flex items-baseline gap-1">
        <span class="font-display text-5xl font-bold text-white" data-price data-monthly="₹{{ number_format($plan->price_monthly) }}" data-yearly="₹{{ number_format(round($yearly / 12)) }}">₹{{ number_format($plan->price_monthly) }}</span>
        <span class="text-slate-500">/month</span>
    </p>
    <p class="mt-1 h-5 text-sm text-slate-500" data-price-note data-monthly="billed monthly" data-yearly="₹{{ number_format($yearly) }} billed yearly{{ $saving ? ' · save ' . $saving . '%' : '' }}">billed monthly</p>
    <ul class="mt-6 flex-1 space-y-3 text-sm">
        <li class="flex gap-3 text-slate-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-volt-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
            {{ $plan->ai_scans_per_month !== null ? number_format($plan->ai_scans_per_month) . ' AI body scans / month' : 'Unlimited AI body scans' }}
        </li>
        @foreach ($plan->features ?? [] as $feature)
            <li class="flex gap-3 text-slate-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-volt-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                {{ $feature }}
            </li>
        @endforeach
    </ul>
    <a href="{{ route('register') }}" class="{{ $featured ? 'btn-primary' : 'btn-ghost' }} mt-8 w-full">{{ $trialDays ? 'Start ' . $trialDays . '-day free trial' : 'Get started' }}</a>
</article>
