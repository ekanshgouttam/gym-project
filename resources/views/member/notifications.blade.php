<x-app-layout title="Notifications">
    @if (auth()->user()->unreadNotifications()->exists())
        <x-slot name="actions">
            <form method="POST" action="{{ route('member.notifications.read-all') }}">@csrf<button class="btn-ghost">Mark all as read</button></form>
        </x-slot>
    @endif

    <div class="max-w-3xl space-y-3">
        @forelse ($notifications as $n)
            @php
                $kind = $n->data['kind'] ?? 'info';
                $dot = ['success' => 'bg-emerald-400', 'warning' => 'bg-amber-400', 'info' => 'bg-sky-400'][$kind] ?? 'bg-sky-400';
            @endphp
            <a href="{{ route('member.notifications.read', $n->id) }}" class="card flex gap-4 p-4 transition hover:border-ink-600 {{ $n->read_at ? 'opacity-70' : '' }}">
                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $n->read_at ? 'bg-ink-600' : $dot }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-medium text-white">{{ $n->data['title'] ?? 'Notification' }}</p>
                        <time class="text-xs text-slate-500" datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->diffForHumans() }}</time>
                    </div>
                    <p class="mt-1 text-sm text-slate-400">{{ $n->data['body'] ?? '' }}</p>
                </div>
            </a>
        @empty
            <x-empty-state title="No notifications" body="Membership updates, scan results and new plans will show up here." />
        @endforelse
    </div>
    <div class="mt-6 max-w-3xl">{{ $notifications->links() }}</div>
</x-app-layout>
