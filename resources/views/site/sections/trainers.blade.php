{{-- 6. Trainers: from the gym's public trainer profiles --}}
<section id="trainers" class="s-section bg-coal-900">
    <div class="s-container">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="s-eyebrow" data-reveal-up>The coaches</p>
                <h2 class="s-h2 mt-5" data-split="lines">Coached by <span class="text-blaze-500">the best.</span></h2>
            </div>
            <a href="#join" class="s-btn-ghost" data-magnetic data-interest="Personal training"><span>Train with a coach</span></a>
        </div>

        <ul class="mt-16 grid gap-5 sm:grid-cols-2 xl:grid-cols-{{ min(4, max(2, $trainers->count())) }}">
            @foreach ($trainers as $t)
                @php $p = $t->trainerProfile; @endphp
                <li class="s-card group relative flex min-h-[460px] flex-col justify-end overflow-hidden p-7" data-reveal-up>
                    @if ($t->avatarUrl())
                        <img src="{{ $t->avatarUrl() }}" alt="{{ $t->name }}" class="absolute inset-0 h-full w-full object-cover grayscale transition duration-700 group-hover:scale-105 group-hover:grayscale-0" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-coal-950 via-coal-950/40 to-transparent"></div>
                    @else
                        {{-- Typographic portrait when no photo is uploaded --}}
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_20%,rgba(255,77,18,.35),transparent_55%)] transition-opacity duration-700 group-hover:opacity-70"></div>
                        <span class="absolute -right-4 top-2 font-brand text-[11rem] leading-none text-white/[.06] transition-transform duration-700 group-hover:-translate-x-3" aria-hidden="true">{{ $t->initials() }}</span>
                    @endif
                    <div class="relative">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blaze-400">{{ optional($p)->specialty ?: 'Coach' }}</p>
                        <h3 class="mt-2 text-4xl">{{ $t->name }}</h3>
                        @if (optional($p)->bio)<p class="mt-3 max-h-0 overflow-hidden text-sm text-zinc-300 opacity-0 transition-all duration-700 group-hover:max-h-40 group-hover:opacity-100 max-lg:max-h-40 max-lg:opacity-100">{{ $p->bio }}</p>@endif
                        <div class="mt-5 flex items-center gap-5 border-t border-white/15 pt-4 text-sm text-zinc-400">
                            @if (optional($p)->experience_years)<span><span class="font-brand text-2xl text-white">{{ $p->experience_years }}</span> yrs</span>@endif
                            @if (optional($p)->certifications)<span class="truncate">{{ $p->certifications }}</span>@endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
