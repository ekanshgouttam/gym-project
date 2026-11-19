@props(['title', 'body' => null])
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-ink-800 text-slate-500">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </span>
    <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
    @if ($body)<p class="mt-1 max-w-sm text-sm text-slate-400">{{ $body }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
