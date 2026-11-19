<x-app-layout title="Progress History">
    @if ($events->isEmpty())
        <x-empty-state title="No progress logged yet" body="Weight logs, body measurements and AI scans will appear here as a timeline.">
            <a href="{{ route('member.weight') }}" class="btn-primary">Log your weight</a>
        </x-empty-state>
    @else
        <div class="max-w-3xl space-y-10">
            @foreach ($grouped as $month => $items)
                <section data-reveal>
                    <h2 class="sticky top-16 z-10 -mx-1 bg-ink-950/90 px-1 py-2 text-sm font-semibold uppercase tracking-widest text-slate-500 backdrop-blur">{{ $month }}</h2>
                    <ol class="relative mt-3 space-y-4 border-l border-ink-700 pl-6">
                        @foreach ($items as $e)
                            @php
                                $dot = ['scan' => 'bg-volt-400', 'weight' => 'bg-sky-400', 'measurement' => 'bg-fuchsia-400'][$e['type']];
                            @endphp
                            <li class="relative">
                                <span class="absolute -left-[1.94rem] top-4 h-3 w-3 rounded-full ring-4 ring-ink-950 {{ $dot }}"></span>
                                <div class="card p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-medium text-white">{{ $e['title'] }}</p>
                                        <time class="text-xs text-slate-500" datetime="{{ $e['date']->toDateString() }}">{{ $e['date']->format('D, d M') }}</time>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-400">{{ $e['detail'] }}</p>
                                    @if ($e['url'])<a href="{{ $e['url'] }}" class="mt-2 inline-block text-sm text-volt-400 hover:text-volt-300">View report →</a>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>
    @endif
</x-app-layout>
