<x-app-layout title="Trainer dashboard">
    <div class="card relative overflow-hidden p-6" data-intro>
        <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-amber-400/10 blur-2xl"></div>
        <p class="text-sm text-slate-400">Good to see you,</p>
        <h2 class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="mt-2 max-w-xl text-sm text-slate-400">Here are the active members at {{ optional(auth()->user()->gym)->name ?? 'your gym' }}.</p>
    </div>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h2 class="p-5 text-base font-semibold">Active members</h2>
        <ul class="divide-y divide-ink-700 border-t border-ink-700">
            @forelse ($members as $member)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-ink-700 text-xs font-semibold text-white">{{ $member->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">{{ $member->name }}</p>
                        <p class="truncate text-xs text-slate-500">Joined {{ $member->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">No active members yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
