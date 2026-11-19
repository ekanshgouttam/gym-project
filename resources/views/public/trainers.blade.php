<x-public-layout title="Trainers">
    <x-page-hero eyebrow="Our trainers" title="Coaches who<br>care about <span class='text-volt-400'>results</span>." lead="Certified, experienced and invested in your progress." />

    <section class="pb-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            @if ($trainers->isEmpty())
                <p class="text-center text-slate-500">Trainer profiles are coming soon.</p>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-stagger>
                    @foreach ($trainers as $trainer)
                        @include('public._trainer-card', ['trainer' => $trainer, 'full' => true])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
