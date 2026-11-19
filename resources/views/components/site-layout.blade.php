@props(['gym', 'site', 'title' => null, 'description' => null])
@php
    $nav = ['about' => 'About', 'programs' => 'Programs', 'training' => 'Training', 'trainers' => 'Trainers', 'pricing' => 'Pricing', 'results' => 'Results', 'gallery' => 'Gallery', 'contact' => 'Contact'];
    $brand = strtoupper(explode(' ', $gym->name)[0]);
    $phone = $gym->phone ? preg_replace('/[^0-9]/', '', $gym->phone) : null;
    $whatsapp = $phone ? 'https://wa.me/' . (strlen($phone) === 10 ? '91' . $phone : $phone) . '?text=' . rawurlencode('Hi ' . $gym->name . ', I would like to know more about membership.') : null;
    $metaTitle = $title ?? ($gym->name . ' — Premium Gym & Personal Training' . ($gym->city ? ' in ' . $gym->city : ''));
    $metaDesc = $description ?? ($site['hero']['subtitle'] . ' Strength, boxing, HIIT and personal training at ' . $gym->name . '.');
    $address = collect([$gym->address, $gym->city])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#050505">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDesc }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:image" content="{{ asset($site['hero']['poster']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <script>
        // Animated content starts hidden only when JS runs; if the motion script never starts, show everything.
        document.documentElement.classList.replace('no-js', 'js');
        setTimeout(function () { var r = document.documentElement; if (!r.classList.contains('motion')) { r.classList.remove('js'); r.classList.add('loaded'); } }, 4000);
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset($site['hero']['poster']) }}" media="(min-width: 768px)">
    <link rel="preload" as="image" href="{{ asset($site['hero']['poster_mobile']) }}" media="(max-width: 767px)">
    <link rel="stylesheet" href="{{ mix('css/site.css') }}">
    <script src="{{ mix('js/site.js') }}" defer></script>
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org', '@type' => 'HealthClub', 'name' => $gym->name, 'url' => url('/'),
        'image' => asset($site['hero']['poster']), 'telephone' => $gym->phone, 'email' => $gym->email,
        'openingHours' => $gym->setting('opening_hours') ?: null,
        'address' => $address ? ['@type' => 'PostalAddress', 'streetAddress' => $gym->address, 'addressLocality' => $gym->city, 'addressCountry' => 'IN'] : null,
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="site">
<a href="#main" class="sr-only z-[110] rounded-full bg-blaze-500 px-5 py-3 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

{{-- Preloader (skipped for reduced motion and on repeat visits in the same session) --}}
<div class="s-loader" data-loader aria-hidden="true">
    <p class="font-brand text-[18vw] leading-none text-white sm:text-[12vw]"><span data-loader-count>0</span><span class="text-blaze-500">%</span></p>
    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.4em] text-zinc-500">{{ $gym->name }}</p>
    <span class="s-loader-bar" data-loader-bar></span>
</div>
<div class="s-curtain" data-curtain aria-hidden="true"></div>
<div class="s-cursor" data-cursor aria-hidden="true"><span data-cursor-label></span></div>
<div class="s-cursor-dot" data-cursor-dot aria-hidden="true"></div>

{{-- Header --}}
<header class="s-header" data-header>
    <div class="s-container flex h-full items-center justify-between gap-6">
        <a href="{{ route('home') }}" class="group flex items-center gap-3" data-magnetic="0.2" aria-label="{{ $gym->name }} home">
            @if ($gym->logoUrl())
                <img src="{{ $gym->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg object-contain" width="36" height="36">
            @else
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-blaze-500 font-brand text-lg text-white">{{ substr($brand, 0, 1) }}</span>
            @endif
            <span class="font-brand text-2xl tracking-wide text-white">{{ $brand }}<span class="text-blaze-500">.</span></span>
        </a>
        <nav class="hidden items-center gap-7 xl:flex" aria-label="Primary">
            @foreach ($nav as $id => $label)
                <a href="#{{ $id }}" class="s-nav-link" data-nav="{{ $id }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="s-nav-link hidden sm:inline-block">My account</a>
            @else
                <a href="{{ route('login') }}" class="s-nav-link hidden sm:inline-block">Member login</a>
            @endauth
            <a href="#join" class="s-btn-primary hidden px-6 py-3 md:inline-flex" data-magnetic><span>Join now</span></a>
            <button type="button" class="relative z-[60] grid h-12 w-12 place-items-center rounded-full border border-white/20 xl:hidden" data-menu-toggle aria-expanded="false" aria-controls="site-menu" aria-label="Open menu">
                <span class="block h-px w-5 bg-white transition-transform duration-300" data-menu-line="1"></span>
                <span class="absolute block h-px w-5 translate-y-1.5 bg-white transition-transform duration-300" data-menu-line="2"></span>
            </button>
        </div>
    </div>
</header>

{{-- Full-screen mobile / tablet menu --}}
<div id="site-menu" class="fixed inset-0 z-[55] hidden flex-col justify-between bg-coal-950 px-6 pb-10 pt-28 sm:px-10" data-menu role="dialog" aria-modal="true" aria-label="Menu">
    <nav class="flex flex-col" aria-label="Mobile">
        @foreach ($nav as $id => $label)
            <a href="#{{ $id }}" class="group flex items-baseline gap-4 overflow-hidden border-b border-white/[.06] py-3" data-menu-link>
                <span class="text-xs font-semibold text-blaze-500">0{{ $loop->iteration }}</span>
                <span class="font-brand text-[11vw] uppercase leading-none text-white transition-colors group-hover:text-blaze-400 sm:text-6xl" data-menu-word>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <div class="mt-8 flex flex-wrap gap-3" data-menu-link>
        <a href="#join" class="s-btn-primary"><span>Join now</span></a>
        <a href="{{ route('login') }}" class="s-btn-ghost"><span>Member login</span></a>
    </div>
</div>

<div id="smooth-wrapper">
    <div id="smooth-content">
        <main id="main">
            {{ $slot }}
        </main>

        <footer class="relative overflow-hidden border-t border-white/[.06] bg-coal-950 pt-24">
            <div class="s-container">
                <div class="grid gap-14 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                    <div>
                        <p class="font-brand text-5xl uppercase leading-none text-white sm:text-6xl">Train with<br><span class="text-blaze-500">purpose.</span></p>
                        <a href="#join" class="s-btn-primary mt-8" data-magnetic><span>Start today</span><span class="s-arrow">→</span></a>
                    </div>
                    <div>
                        <p class="s-label">Visit</p>
                        <p class="text-zinc-300">{{ $address ?: $gym->name }}</p>
                        @if ($gym->setting('opening_hours'))<p class="mt-3 text-sm text-zinc-500">{{ $gym->setting('opening_hours') }}</p>@endif
                    </div>
                    <div>
                        <p class="s-label">Talk to us</p>
                        @if ($gym->phone)<a href="tel:{{ $phone }}" class="block text-zinc-300 hover:text-white">{{ $gym->phone }}</a>@endif
                        @if ($gym->email)<a href="mailto:{{ $gym->email }}" class="mt-1 block text-zinc-300 hover:text-white">{{ $gym->email }}</a>@endif
                        @if ($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="mt-1 block text-zinc-300 hover:text-white">WhatsApp</a>@endif
                    </div>
                    <div>
                        <p class="s-label">Explore</p>
                        <ul class="grid grid-cols-2 gap-x-6 gap-y-2 text-zinc-400">
                            @foreach ($nav as $id => $label)<li><a href="#{{ $id }}" class="hover:text-white">{{ $label }}</a></li>@endforeach
                            <li><a href="{{ route('login') }}" class="hover:text-white">Member login</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <p class="pointer-events-none mt-20 select-none whitespace-nowrap text-center font-brand text-[22vw] leading-[.8] text-white/[.04]" aria-hidden="true">{{ $brand }}</p>
            <div class="border-t border-white/[.06] py-6">
                <div class="s-container flex flex-wrap items-center justify-between gap-3 text-xs text-zinc-600">
                    <p>&copy; {{ date('Y') }} {{ $gym->name }}. All rights reserved.</p>
                    <p>Powered by <a href="{{ route('flexfit') }}" class="text-zinc-400 hover:text-white">FlexFit</a> · AI body reports are visual observations, not medical measurements.</p>
                </div>
            </div>
        </footer>
    </div>
</div>

{{-- Floating actions --}}
@if ($whatsapp)
    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-white shadow-[0_10px_40px_-10px_rgba(37,211,102,.6)] transition-transform hover:scale-110 sm:bottom-8 sm:right-8" data-cursor-text="Chat" aria-label="Chat with us on WhatsApp">
        <svg viewBox="0 0 32 32" class="h-7 w-7" fill="currentColor" aria-hidden="true"><path d="M16 3C9 3 3.4 8.6 3.4 15.5c0 2.3.6 4.5 1.8 6.4L3.3 29l7.3-1.9c1.8 1 3.9 1.5 5.9 1.5h.1C23.5 28.6 29 23 29 16.1 29 9.1 23.4 3 16 3zm0 23.3c-1.9 0-3.7-.5-5.3-1.4l-.4-.2-4.3 1.1 1.2-4.2-.3-.4c-1-1.6-1.5-3.5-1.5-5.4C5.4 10 10.2 5.3 16 5.3S26.7 10 26.7 16 21.9 26.3 16 26.3zm5.8-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.3-.5-2.5-1.6-.9-.8-1.6-1.9-1.7-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.7 5.8 5.2.8.4 1.4.6 1.9.7.8.3 1.6.2 2.2.1.7-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
    </a>
@endif
<button type="button" class="fixed bottom-5 left-5 z-40 grid h-12 w-12 translate-y-24 place-items-center rounded-full border border-white/15 bg-coal-900/80 text-white opacity-0 backdrop-blur transition-all duration-500 hover:border-blaze-500 hover:text-blaze-400 sm:bottom-8 sm:left-8" data-to-top aria-label="Back to top">
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

{{-- Gallery lightbox --}}
<div class="s-lightbox" data-lightbox hidden role="dialog" aria-modal="true" aria-label="Gallery viewer">
    <button type="button" class="absolute right-5 top-5 grid h-12 w-12 place-items-center rounded-full border border-white/20 text-white hover:border-blaze-500" data-lightbox-close aria-label="Close">✕</button>
    <div class="max-h-[85vh] w-full max-w-6xl" data-lightbox-stage></div>
</div>
</body>
</html>
