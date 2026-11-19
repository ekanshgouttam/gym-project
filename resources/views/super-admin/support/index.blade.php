<x-app-layout title="Support requests">
    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter support requests">
        @php $tabs = ['unresolved' => 'Needs attention', 'open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'all' => 'All']; @endphp
        @foreach ($tabs as $key => $label)
            @php
                $n = $key === 'unresolved' ? ($counts['open'] ?? 0) + ($counts['in_progress'] ?? 0) : ($key === 'all' ? $counts->sum() : ($counts[$key] ?? 0));
            @endphp
            <a href="{{ route('super-admin.support.index', ['status' => $key]) }}" class="badge px-3 py-1.5 text-sm {{ $status === $key ? 'bg-volt-400 text-ink-950' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $n }}</span></a>
        @endforeach
    </nav>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-700">
                <thead class="bg-ink-850/60"><tr><th class="table-head">Request</th><th class="table-head">Gym</th><th class="table-head">Category</th><th class="table-head">Priority</th><th class="table-head">Status</th><th class="table-head">Opened</th></tr></thead>
                <tbody class="divide-y divide-ink-700">
                    @forelse ($tickets as $t)
                        <tr class="cursor-pointer hover:bg-ink-850/40" onclick="window.location='{{ route('super-admin.support.show', $t) }}'">
                            <td class="table-cell"><a href="{{ route('super-admin.support.show', $t) }}" class="font-medium text-white hover:text-volt-300">{{ $t->subject }}</a><p class="text-xs text-slate-500">{{ optional($t->user)->name }}</p></td>
                            <td class="table-cell text-slate-400">{{ optional($t->gym)->name ?: '—' }}</td>
                            <td class="table-cell text-slate-400">{{ \App\Models\SupportRequest::CATEGORIES[$t->category] ?? $t->category }}</td>
                            <td class="table-cell"><span class="badge {{ $t->priorityBadge() }}">{{ \App\Models\SupportRequest::PRIORITIES[$t->priority] }}</span></td>
                            <td class="table-cell"><span class="badge {{ $t->statusBadge() }}">{{ \App\Models\SupportRequest::STATUSES[$t->status] }}</span></td>
                            <td class="table-cell text-slate-400">{{ $t->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No support requests here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $tickets->links() }}</div>
</x-app-layout>
