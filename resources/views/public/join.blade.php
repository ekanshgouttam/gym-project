<x-public-layout title="Join Gym">
    <x-page-hero eyebrow="Join Gym" title="Choose your<br><span class='text-volt-400'>home</span> branch." lead="At the gym? Just scan the QR code at the front desk. Joining from home works the same way." />

    <section class="pb-24">
        <div class="mx-auto grid max-w-5xl gap-6 px-4 sm:px-6 md:grid-cols-2" data-stagger>
            @forelse ($gyms as $gym)
                <a href="{{ route('join.show', $gym->join_token) }}" data-tilt class="card group block p-7 transition hover:border-volt-400/50">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold">{{ $gym->name }}</h2>
                            <p class="mt-2 text-slate-400">{{ collect([$gym->address, $gym->city])->filter()->implode(', ') }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-volt-400 text-ink-950 transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </div>
                </a>
            @empty
                <p class="text-center text-slate-500 md:col-span-2">Registration opens soon.</p>
            @endforelse
        </div>
        <p class="mt-10 text-center text-sm text-slate-500">Already a member? <a href="{{ route('login') }}" class="text-volt-400 hover:text-volt-300">Log in</a></p>
    </section>
</x-public-layout>
