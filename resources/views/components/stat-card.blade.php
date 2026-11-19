@props(['label', 'value', 'hint' => null, 'prefix' => null, 'decimals' => 0])
<div class="card p-5" data-intro>
    <p class="text-sm text-slate-400">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl font-bold text-white">@if ($prefix)<span class="mr-0.5 text-volt-400">{{ $prefix }}</span>@endif<span data-count="{{ $decimals ? round((float) $value, $decimals) : (int) round((float) $value) }}" data-decimals="{{ (int) $decimals }}">{{ number_format((float) $value, (int) $decimals) }}</span></p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
