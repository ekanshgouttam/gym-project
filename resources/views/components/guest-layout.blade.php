<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full">
    <div class="relative grid min-h-screen lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden border-r border-ink-700 bg-ink-900 lg:block">
            <div class="grid-bg absolute inset-0"></div>
            <div class="absolute -left-24 top-1/3 h-96 w-96 rounded-full bg-volt-400/20 blur-3xl"></div>
            <div class="relative flex h-full flex-col justify-between p-12">
                <x-logo />
                <div data-intro>
                    <p class="text-sm font-semibold uppercase tracking-widest text-volt-400">Train smarter</p>
                    <h2 class="mt-4 max-w-md text-4xl font-bold leading-tight">Your gym, your trainers and your progress — all in one place.</h2>
                    <p class="mt-4 max-w-md text-slate-400">Track every rep, every session and every transformation milestone.</p>
                </div>
                <p class="text-xs text-slate-600">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <x-logo class="mb-10 lg:hidden" />
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
