@props(['title' => null, 'description' => null])
@php
    $links = [
        'home' => 'Home', 'features' => 'Features', 'how-it-works' => 'How It Works', 'pricing' => 'Pricing',
        'gyms' => 'Find a Gym', 'about' => 'About',
    ];
    $supportEmail = \App\Models\PlatformSetting::get('support_email');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="description" content="{{ $description ?? 'FlexFit is the all-in-one platform for gyms: QR member registration, fees, attendance, trainers and AI body transformation tracking.' }}">
    {{ $head ?? '' }}
</head>
<body class="overflow-x-hidden">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-volt-400 focus:px-4 focus:py-2 focus:text-ink-950">Skip to content</a>

<header class="fixed inset-x-0 top-0 z-50 border-b border-white/5 bg-ink-950/70 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
        <x-logo />
        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary">
            @foreach ($links as $route => $label)
                @php $active = request()->routeIs($route) || ($route === 'gyms' && request()->routeIs('gyms.*')); @endphp
                <a href="{{ route($route) }}" class="rounded-lg px-3 py-2 text-sm transition {{ $active ? 'text-volt-400' : 'text-slate-400 hover:text-white' }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
            @else
                <a href="{{ route('demo') }}" class="btn-ghost hidden xl:inline-flex">Book a Demo</a>
                <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Login</a>
                <a href="{{ route('register') }}" class="btn-primary">Register Gym</a>
            @endauth
            <button type="button" class="btn-ghost px-2.5 lg:hidden" data-toggle="#mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Open menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
    <nav id="mobile-nav" class="hidden border-t border-white/5 bg-ink-950/95 px-4 pb-6 pt-2 lg:hidden" aria-label="Mobile">
        @foreach ($links as $route => $label)
            <a href="{{ route($route) }}" class="block rounded-lg px-3 py-3 text-base {{ request()->routeIs($route) ? 'text-volt-400' : 'text-slate-300' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('demo') }}" class="block rounded-lg px-3 py-3 text-base {{ request()->routeIs('demo') ? 'text-volt-400' : 'text-slate-300' }}">Book a Demo</a>
        @guest
            <a href="{{ route('login') }}" class="mt-2 block rounded-lg px-3 py-3 text-base text-slate-300">Login</a>
            <a href="{{ route('register') }}" class="btn-primary mt-3 w-full">Register Gym</a>
        @endguest
    </nav>
</header>

<main id="main">
    {{ $slot }}
</main>

<footer class="relative overflow-hidden border-t border-ink-700 bg-ink-900/60">
    <div class="absolute -bottom-40 left-1/2 h-80 w-[40rem] -translate-x-1/2 rounded-full bg-volt-400/10 blur-[120px]" aria-hidden="true"></div>
    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-5">
        <div class="md:col-span-2">
            <x-logo />
            <p class="mt-4 max-w-sm text-sm text-slate-400">One platform for gym owners: QR member registration, fees, attendance, trainers and AI body transformation tracking.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn-primary">Register your gym</a>
                <a href="{{ route('demo') }}" class="btn-ghost">Book a demo</a>
            </div>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Product</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <li><a href="{{ route('features') }}" class="hover:text-white">Features</a></li>
                <li><a href="{{ route('how-it-works') }}" class="hover:text-white">How it works</a></li>
                <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Members</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <li><a href="{{ route('gyms') }}" class="hover:text-white">Find your gym</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white">Member login</a></li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Company</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
                <li><a href="{{ route('demo') }}" class="hover:text-white">Book a demo</a></li>
                @if ($supportEmail)<li><a href="mailto:{{ $supportEmail }}" class="hover:text-white">{{ $supportEmail }}</a></li>@endif
            </ul>
        </div>
    </div>
    <div class="relative border-t border-ink-700 py-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('app.name') }}. AI body reports are visual observations, not medical measurements.</div>
</footer>
{{ $scripts ?? '' }}
</body>
</html>
