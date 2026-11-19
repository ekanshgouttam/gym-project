<article data-tilt class="group card overflow-hidden">
    <div class="relative aspect-[4/5] overflow-hidden bg-gradient-to-br from-ink-800 via-ink-850 to-ink-900">
        @if ($trainer->avatarUrl())
            <img src="{{ $trainer->avatarUrl() }}" alt="{{ $trainer->name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">
        @else
            <div class="grid-bg absolute inset-0 opacity-50"></div>
            <span class="absolute inset-0 grid place-items-center font-display text-7xl font-bold text-ink-600 transition group-hover:text-volt-400/70">{{ $trainer->initials() }}</span>
        @endif
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-ink-950/90 to-transparent"></div>
        @if ($trainer->trainerProfile->experience_years)
            <span class="badge absolute left-4 top-4 bg-ink-950/70 text-volt-300 backdrop-blur">{{ $trainer->trainerProfile->experience_years }} yrs experience</span>
        @endif
    </div>
    <div class="p-5">
        <h3 class="text-lg font-semibold">{{ $trainer->name }}</h3>
        <p class="text-sm text-volt-400">{{ $trainer->trainerProfile->specialty ?: 'Personal trainer' }}</p>
        @if (! empty($full) && $trainer->trainerProfile->bio)
            <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $trainer->trainerProfile->bio }}</p>
        @endif
        @if (! empty($full) && $trainer->trainerProfile->certifications)
            <p class="mt-3 text-xs text-slate-500">{{ $trainer->trainerProfile->certifications }}</p>
        @endif
        @if ($trainer->gym)
            <p class="mt-3 text-xs text-slate-500">{{ $trainer->gym->name }}</p>
        @endif
    </div>
</article>
