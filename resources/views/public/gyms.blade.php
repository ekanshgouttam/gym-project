<x-public-layout title="Find a gym">
    <x-page-hero eyebrow="Find a gym" title="Find your gym.<br><span class='text-volt-400'>Join</span> in two minutes." lead="At the gym? Just scan the QR code at the front desk. Joining from home works the same way.">
        <form method="GET" action="{{ route('gyms') }}" class="mx-auto mt-10 flex max-w-xl gap-2" data-intro role="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by gym name or city" class="input py-3" aria-label="Search gyms">
            <button type="submit" class="btn-primary shrink-0">Search</button>
        </form>
        @if ($cities->count() > 1)
            <div class="mt-4 flex flex-wrap justify-center gap-2" data-intro>
                @foreach ($cities as $city)
                    <a href="{{ route('gyms', ['q' => $city]) }}" class="badge px-3 py-1.5 text-sm {{ request('q') === $city ? 'bg-volt-400 text-on-volt' : 'bg-ink-800 text-slate-300 hover:text-white' }}">{{ $city }}</a>
                @endforeach
            </div>
        @endif
    </x-page-hero>

    <section class="pb-24">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            @if (request('q'))
                <p class="mb-6 text-sm text-slate-400">{{ $gyms->count() }} {{ \Illuminate\Support\Str::plural('gym', $gyms->count()) }} for “{{ request('q') }}” · <a href="{{ route('gyms') }}" class="text-volt-400 hover:text-volt-300">show all</a></p>
            @endif
            <div class="grid gap-4 md:grid-cols-2" data-stagger>
                @forelse ($gyms as $gym)
                    @include('public._gym-card', ['gym' => $gym])
                @empty
                    <p class="text-center text-slate-500 md:col-span-2">No gyms found. Is your gym not on FlexFit yet? Tell the owner about us.</p>
                @endforelse
            </div>
            <p class="mt-10 text-center text-sm text-slate-500">Already a member? <a href="{{ route('login') }}" class="text-volt-400 hover:text-volt-300">Log in</a> · Own a gym? <a href="{{ route('register') }}" class="text-volt-400 hover:text-volt-300">Register it</a></p>
        </div>
    </section>
</x-public-layout>
