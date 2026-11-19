{{-- Front / side / back photo pickers with pose outlines and live preview. Vars: $disabled (bool) --}}
@php
    $poses = [
        'front' => ['Front', 'Face the camera, arms slightly away from your sides', '<circle cx="24" cy="9" r="5"/><path d="M24 14v20M24 34l-7 18M24 34l7 18M13 19l11 3 11-3M13 19l-3 14M35 19l3 14"/>'],
        'side' => ['Side', 'Turn 90° to your left, arms relaxed by your sides', '<circle cx="24" cy="9" r="5"/><path d="M24 14v20M24 34l-2 18M24 34l3 18M24 20l-1 14"/>'],
        'back' => ['Back', 'Face away from the camera, same stance as front', '<circle cx="24" cy="9" r="5"/><path d="M24 14v20M24 34l-7 18M24 34l7 18M13 19l11 3 11-3M13 19l-3 14M35 19l3 14"/><path d="M21 20h6" stroke-dasharray="2 2"/>'],
    ];
@endphp
<div class="grid grid-cols-3 gap-3">
    @foreach ($poses as $view => [$label, $hint, $svg])
        <div>
            <label class="group relative block cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-ink-600 bg-ink-850 transition hover:border-volt-400/60 focus-within:border-volt-400">
                <span class="sr-only">{{ $label }} body photo. {{ $hint }}</span>
                <div class="relative aspect-[3/4]">
                    <div data-preview-placeholder class="absolute inset-0 flex flex-col items-center justify-center gap-1 p-2 text-center text-slate-500">
                        <svg class="h-14 w-14" viewBox="0 0 48 56" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">{!! $svg !!}</svg>
                        <span class="text-sm font-medium text-slate-300">{{ $label }}</span>
                        <span class="text-[11px] leading-tight">Tap to add</span>
                    </div>
                    <img id="preview-{{ $view }}" alt="" class="hidden h-full w-full object-cover">
                </div>
                <input type="file" name="{{ $view }}" accept="image/jpeg,image/png,image/webp" capture="environment" data-preview="#preview-{{ $view }}" class="absolute inset-0 cursor-pointer opacity-0" required {{ ! empty($disabled) ? 'disabled' : '' }}>
            </label>
            <p class="mt-1.5 text-[11px] leading-snug text-slate-500">{{ $hint }}</p>
        </div>
    @endforeach
</div>
@foreach (['front', 'side', 'back'] as $v)
    @error($v) <p class="field-error">{{ $message }}</p> @enderror
@endforeach
