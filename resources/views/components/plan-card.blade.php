@props(['plan', 'cta' => null])
{{-- Interactive pricing card: pointer tilt + spotlight that follows the cursor. --}}
<article data-tilt class="group relative flex flex-col overflow-hidden rounded-3xl border p-7 transition-colors {{ $plan->is_featured ? 'border-volt-400/60 bg-ink-850 shadow-glow' : 'border-ink-700 bg-ink-900/80 hover:border-ink-600' }}">
    <div class="pointer-events-none absolute inset-0 opacity-0 transition group-hover:opacity-100" style="background: radial-gradient(400px circle at var(--mx, 50%) var(--my, 0%), rgba(255,212,0,.12), transparent 40%);"></div>
    @if ($plan->is_featured)
        <span class="badge absolute right-5 top-5 bg-volt-400 text-ink-950">Most popular</span>
    @endif
    <p class="text-sm font-medium uppercase tracking-widest text-slate-400">{{ $plan->durationLabel() }}</p>
    <h3 class="mt-2 text-2xl font-bold">{{ $plan->name }}</h3>
    @if ($plan->gym)
        <p class="mt-1 text-xs text-slate-500">{{ $plan->gym->name }} only</p>
    @endif
    <p class="mt-6 flex items-baseline gap-1">
        <span class="font-display text-5xl font-bold text-white">₹{{ number_format((float) $plan->price) }}</span>
    </p>
    <p class="mt-1 text-sm text-slate-500">
        @if ($plan->duration_months > 1) ≈ ₹{{ number_format($plan->monthlyPrice()) }}/month @else billed monthly @endif
    </p>
    @if ($plan->description)
        <p class="mt-4 text-sm text-slate-400">{{ $plan->description }}</p>
    @endif
    <ul class="mt-6 flex-1 space-y-3 text-sm">
        @foreach ($plan->features ?? [] as $feature)
            <li class="flex gap-3 text-slate-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-volt-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                {{ $feature }}
            </li>
        @endforeach
    </ul>
    {{ $cta ?? '' }}
    @unless ($cta)
        <a href="{{ route('join') }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} mt-8 w-full">Choose {{ $plan->name }}</a>
    @endunless
</article>
