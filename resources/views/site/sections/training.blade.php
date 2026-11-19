{{-- 5. Personal training: sticky headline with a step-by-step story beside it --}}
@php $pt = $site['personal_training']; @endphp
<section id="training" class="s-section bg-coal-950">
    <div class="s-container grid gap-16 lg:grid-cols-2">
        <div class="lg:sticky lg:top-28 lg:self-start">
            <p class="s-eyebrow" data-reveal-up>Personal training</p>
            <h2 class="s-h2 mt-6" data-split="lines">
                @foreach ($pt['title'] as $line)<span class="block {{ $loop->last ? 'text-blaze-500' : '' }}">{{ $line }}</span>@endforeach
            </h2>
            <p class="s-lead mt-8 max-w-md" data-reveal-up>{{ $pt['lead'] }}</p>
            <div class="mt-10 flex flex-wrap gap-3" data-reveal-up>
                <a href="#join" class="s-btn-primary" data-magnetic data-interest="Personal training"><span>Book a consultation</span><span class="s-arrow">→</span></a>
                <a href="#trainers" class="s-btn-ghost" data-magnetic><span>Meet the coaches</span></a>
            </div>
            <figure class="s-card s-zoom mt-14 hidden aspect-[16/10] lg:block" data-reveal-clip>
                <img src="{{ asset($pt['images'][1]) }}" alt="Coach-led dumbbell training" class="h-full w-full object-cover object-top" loading="lazy" width="1600" height="1000">
            </figure>
        </div>

        <div>
            <figure class="s-card s-zoom aspect-[4/5]" data-reveal-clip>
                <img src="{{ asset($pt['images'][0]) }}" srcset="{{ asset(str_replace('.webp', '-sm.webp', $pt['images'][0])) }} 800w, {{ asset($pt['images'][0]) }} 1600w" sizes="(min-width: 1024px) 45vw, 100vw"
                     alt="A coach guiding a client through a session" class="h-full w-full object-cover" loading="lazy" width="1600" height="2000" data-speed="0.95">
            </figure>
            <ol class="mt-12 space-y-4">
                @foreach ($pt['steps'] as $i => [$title, $text])
                    <li class="group flex gap-6 rounded-[1.5rem] border border-white/[.07] bg-coal-900 p-6 transition-colors duration-500 hover:border-blaze-500/50 sm:p-8" data-reveal-up>
                        <span class="font-brand text-5xl leading-none text-white/15 transition-colors duration-500 group-hover:text-blaze-500">{{ $i + 1 }}</span>
                        <div><h3 class="text-3xl">{{ $title }}</h3><p class="mt-2 text-zinc-500">{{ $text }}</p></div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
