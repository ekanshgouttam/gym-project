{{-- 9. Gallery: editorial grid, zoom on hover, lightbox with video --}}
<section id="gallery" class="s-section bg-coal-950">
    <div class="s-container">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="s-eyebrow" data-reveal-up>Inside the gym</p>
                <h2 class="s-h2 mt-5" data-split="lines">Where the <span class="text-blaze-500">work</span> happens.</h2>
            </div>
            <p class="max-w-sm text-zinc-500" data-reveal-up>Open from {{ $gym->setting('opening_hours') ?: 'early morning to late night' }}.</p>
        </div>

        <ul class="mt-16 grid auto-rows-[220px] grid-cols-2 gap-3 sm:auto-rows-[260px] sm:gap-5 lg:grid-cols-4">
            @foreach ($site['gallery'] as $item)
                @php $span = $item['size'] === 'tall' ? 'row-span-2' : 'col-span-2'; @endphp
                <li class="{{ $span }}" data-reveal-clip>
                    <button type="button" class="s-card s-zoom group relative block h-full w-full text-left" data-gallery-item data-type="{{ $item['type'] }}" data-src="{{ asset($item['src']) }}" data-alt="{{ $item['alt'] }}" data-cursor-text="{{ $item['type'] === 'video' ? 'Play' : 'View' }}" aria-label="Open: {{ $item['alt'] }}">
                        @if ($item['type'] === 'video')
                            <video class="absolute inset-0 h-full w-full object-cover" muted loop playsinline preload="none" poster="{{ asset($item['poster']) }}" data-hover-video data-src="{{ asset($item['src']) }}"></video>
                            <span class="absolute left-1/2 top-1/2 grid h-16 w-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-blaze-500 text-white transition-transform duration-500 group-hover:scale-110" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="ml-1 h-6 w-6" fill="currentColor"><path d="M7 4.5v15l13-7.5z"/></svg>
                            </span>
                        @else
                            <img src="{{ asset($item['src']) }}" alt="{{ $item['alt'] }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy" width="1600" height="1100">
                        @endif
                        <span class="absolute inset-0 bg-coal-950/0 transition-colors duration-500 group-hover:bg-coal-950/20"></span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</section>
