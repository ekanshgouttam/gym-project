@php($compact = $compact ?? false)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-ink-700">
        <thead class="bg-ink-850/60">
            <tr>
                <th class="table-head">Name</th>
                <th class="table-head">Role</th>
                @if (auth()->user()->isSuperAdmin())<th class="table-head">Gym</th>@endif
                <th class="table-head">Status</th>
                <th class="table-head">Joined</th>
                @unless ($compact)<th class="table-head"><span class="sr-only">Actions</span></th>@endunless
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-700">
            @forelse ($users as $u)
                <tr class="hover:bg-ink-850/40">
                    <td class="table-cell">
                        <div class="flex items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink-700 text-xs font-semibold text-white">{{ $u->initials() }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-white">{{ $u->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="table-cell"><x-role-badge :role="$u->primaryRole()" /></td>
                    @if (auth()->user()->isSuperAdmin())<td class="table-cell text-slate-400">{{ optional($u->gym)->name ?? '—' }}</td>@endif
                    <td class="table-cell">
                        <span class="badge {{ $u->isActive() ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300' }}">{{ ucfirst($u->status) }}</span>
                    </td>
                    <td class="table-cell whitespace-nowrap text-slate-400">{{ $u->created_at->format('d M Y') }}</td>
                    @unless ($compact)
                        <td class="table-cell">
                            <div class="flex items-center justify-end gap-2">
                                @if ($u->primaryRole() === \App\Support\Roles::MEMBER && auth()->user()->can('manage members') && (auth()->user()->isSuperAdmin() || auth()->user()->gym_id === $u->gym_id))
                                    <a href="{{ route('manage.scans.index', $u) }}" class="btn-ghost px-3 py-1.5 text-xs">Scans</a>
                                @endif
                                @can('update', $u)
                                    <a href="{{ route('manage.users.edit', $u) }}" class="btn-ghost px-3 py-1.5 text-xs">Edit</a>
                                @endcan
                                @can('changeStatus', $u)
                                    <form method="POST" action="{{ route('manage.users.status', $u) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn-ghost px-3 py-1.5 text-xs">{{ $u->isActive() ? 'Suspend' : 'Activate' }}</button>
                                    </form>
                                @endcan
                                @can('delete', $u)
                                    <form method="POST" action="{{ route('manage.users.destroy', $u) }}" data-confirm="Delete {{ $u->name }}? This can't be undone.">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger px-3 py-1.5 text-xs">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    @endunless
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
