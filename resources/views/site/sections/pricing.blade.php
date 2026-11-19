{{-- 7. Membership pricing: the gym's live plans, interactive spotlight cards --}}
@php $featured = $plans->firstWhere('is_featured', true) ?: $plans->get((int) floor($plans->count() / 2)); @endphp
<section id="pricing" class="s-section relative overflow-hidden bg-coal-950">
    <div class="pointer-events-none absolute left-1/2 top-0 h-[40rem] w-[60rem] -translate-x-1/2 rounded-full bg-blaze-500/10 blur-[140px]"></div>
    <div class="s-container relative">
        <div class="mx-auto max-w-3xl text-center">
            <p class="s-eyebrow justify-center" data-reveal-up>Membership</p>
            <h2 class="s-h2 mt-5" data-split="lines">Invest in <span class="text-blaze-500">yourself.</span></h2>
            <p class="s-lead mt-6" data-reveal-up>No joining fee. Every plan includes all programs, the member app and monthly AI body scans.</p>
        </div>

        <ul class="mt-16 grid gap-5 md:grid-cols-2 xl:grid-cols-{{ min(4, max(2, $plans->count())) }}" data-pricing>
            @foreach ($plans as $plan)
                @php $isFeatured = $featured && $plan->id === $featured->id; @endphp
                <li class="group relative flex flex-col overflow-hidden rounded-[1.75rem] border p-8 transition-transform duration-500 hover:-translate-y-2 {{ $isFeatured ? 'border-blaze-500 bg-blaze-500 text-white' : 'border-white/[.08] bg-coal-900' }}" data-spotlight data-reveal-up>
                    <div class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-500 group-hover:opacity-100" style="background: radial-gradient(420px circle at var(--mx, 50%) var(--my, 0%), {{ $isFeatured ? 'rgba(255,255,255,.22)' : 'rgba(255,77,18,.16)' }}, transparent 45%);"></div>
                    <div class="relative flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] {{ $isFeatured ? 'text-white/80' : 'text-zinc-500' }}">{{ $plan->durationLabel() }}</p>
                        @if ($isFeatured)<span class="rounded-full bg-coal-950 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-white">Most popular</span>@endif
                    </div>
                    <h3 class="relative mt-3 text-4xl">{{ $plan->name }}</h3>
                    <p class="relative mt-8 font-brand text-6xl text-white">₹{{ number_format((float) $plan->price) }}</p>
                    <p class="relative mt-1 text-sm {{ $isFeatured ? 'text-white/80' : 'text-zinc-500' }}">{{ $plan->duration_months > 1 ? '≈ ₹' . number_format($plan->monthlyPrice()) . ' per month' : 'billed monthly' }}</p>
                    @if ($plan->description)<p class="relative mt-5 text-sm {{ $isFeatured ? 'text-white/90' : 'text-zinc-400' }}">{{ $plan->description }}</p>@endif
                    <ul class="relative mt-6 flex-1 space-y-3 text-sm">
                        @foreach ($plan->features ?? [] as $f)
                            <li class="flex gap-3"><span class="{{ $isFeatured ? 'text-coal-950' : 'text-blaze-500' }}">✦</span><span class="{{ $isFeatured ? 'text-white' : 'text-zinc-300' }}">{{ $f }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ $gym->joinUrl() }}" class="relative mt-8 {{ $isFeatured ? 's-btn bg-coal-950 text-white hover:bg-white hover:text-coal-950' : 's-btn-ghost' }}" data-magnetic data-page-link><span>Choose {{ $plan->name }}</span></a>
                </li>
            @endforeach
        </ul>
        <p class="mt-10 text-center text-sm text-zinc-500" data-reveal-up>Not sure yet? <a href="#join" class="font-semibold text-white underline decoration-blaze-500 underline-offset-4 hover:text-blaze-400">Book a free trial session</a> first.</p>
    </div>
</section>
