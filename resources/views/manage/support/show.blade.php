<x-app-layout :title="'Support · ' . $ticket->subject">
    <section class="card max-w-3xl p-6" data-intro>
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportRequest::STATUSES[$ticket->status] }}</span>
            <span class="badge bg-ink-800 text-slate-300">{{ \App\Models\SupportRequest::CATEGORIES[$ticket->category] ?? $ticket->category }}</span>
        </div>
        <h2 class="mt-4 text-xl font-semibold">{{ $ticket->subject }}</h2>
        <p class="mt-1 text-sm text-slate-500">Sent {{ $ticket->created_at->format('d M Y, H:i') }}</p>
        <div class="mt-5 whitespace-pre-line rounded-xl border border-ink-700 bg-ink-850 p-4 text-sm text-slate-200">{{ $ticket->message }}</div>

        <h3 class="mt-8 text-sm font-semibold uppercase tracking-widest text-slate-500">FlexFit reply</h3>
        @if ($ticket->admin_reply)
            <div class="mt-3 whitespace-pre-line rounded-xl border border-volt-400/30 bg-volt-400/5 p-4 text-sm text-slate-100">{{ $ticket->admin_reply }}</div>
            <p class="mt-1 text-xs text-slate-500">{{ optional($ticket->replied_at)->diffForHumans() }}</p>
        @else
            <p class="mt-3 text-sm text-slate-400">No reply yet. We usually answer within one working day.</p>
        @endif

        <div class="mt-8"><a href="{{ route('manage.support.index') }}" class="btn-ghost">Back to support</a></div>
    </section>
</x-app-layout>
