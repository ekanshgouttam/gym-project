{{-- Gym card for the directory and home page. --}}
<a href="{{ route('gyms.show', $gym) }}" data-tilt class="card group flex items-center gap-4 p-5 transition hover:border-volt-400/50">
    @if ($gym->logoUrl())
        <img src="{{ $gym->logoUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl border border-ink-700 bg-ink-850 object-contain p-1">
    @else
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-volt-400 to-ember-500 font-display text-lg font-bold text-on-volt">{{ strtoupper(substr($gym->name, 0, 2)) }}</span>
    @endif
    <div class="min-w-0 flex-1">
        <h3 class="truncate text-lg font-semibold">{{ $gym->name }}</h3>
        <p class="truncate text-sm text-slate-400">{{ collect([$gym->address, $gym->city])->filter()->implode(', ') ?: 'Location coming soon' }}</p>
    </div>
    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-800 text-volt-400 transition group-hover:translate-x-1 group-hover:bg-volt-400 group-hover:text-on-volt" aria-hidden="true">→</span>
</a>
