<x-app-layout :title="auth()->user()->isSuperAdmin() ? 'Users' : 'Members & Trainers'">
    <x-slot name="actions">
        @if (auth()->user()->hasRole(\App\Support\Roles::GYM_ADMIN))
            <a href="{{ route('manage.members.import') }}" class="btn-ghost">Import CSV</a>
            <a href="{{ route('manage.users.create', ['role' => 'trainer']) }}" class="btn-ghost">Add trainer</a>
            <a href="{{ route('manage.members.create') }}" class="btn-primary">Add member</a>
        @else
            <a href="{{ route('manage.users.create') }}" class="btn-primary">Add user</a>
        @endif
    </x-slot>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto_auto]" role="search">
        <label class="sr-only" for="q">Search</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search name, email or phone" class="input">
        <label class="sr-only" for="role">Role</label>
        <select id="role" name="role" class="input">
            <option value="">All roles</option>
            @foreach ($assignable as $role)
                <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ \App\Support\Roles::label($role) }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status" class="input">
            <option value="">Any status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        <button class="btn-ghost">Filter</button>
    </form>

    <div class="card overflow-hidden">
        @include('manage.users._table', ['users' => $users])
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</x-app-layout>
