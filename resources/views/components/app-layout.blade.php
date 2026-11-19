<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full">
@php
    $user = auth()->user();
    $navGroups = \App\Support\Navigation::for($user);
@endphp
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col overflow-y-auto border-r border-ink-700 bg-ink-900 lg:sticky lg:top-0 lg:flex lg:h-screen">
        <div class="flex h-16 shrink-0 items-center px-5"><x-logo /></div>
        <nav class="flex-1 space-y-5 px-3 py-4" aria-label="Main">
            @foreach ($navGroups as $group => $items)
                <div class="space-y-1">
                    @if ($group)<p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-600">{{ $group }}</p>@endif
                    @foreach ($items as $item)
                        <a href="{{ $item['route'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if ($item['badge'])<span class="badge bg-volt-400 text-ink-950">{{ $item['badge'] }}</span>@endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="border-t border-ink-700 p-4">
            <div class="flex items-center gap-3">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                @else
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-700 text-sm font-semibold text-white">{{ $user->initials() }}</span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->roleLabel() }}{{ $user->gym ? ' · ' . $user->gym->name : '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button class="btn-ghost w-full">Sign out</button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-ink-700 bg-ink-950/80 px-4 backdrop-blur sm:px-8">
            <button type="button" class="btn-ghost px-2.5 lg:hidden" data-toggle="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="truncate text-lg font-semibold">{{ $title ?? 'Dashboard' }}</h1>
            <div class="ml-auto">{{ $actions ?? '' }}</div>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
