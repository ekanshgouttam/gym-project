<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-display text-lg font-bold text-white']) }}>
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-volt-400 text-ink-950">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 7v10M3 9.5v5M18 7v10M21 9.5v5M6 12h12"/></svg>
    </span>
    {{ config('app.name') }}
</a>
