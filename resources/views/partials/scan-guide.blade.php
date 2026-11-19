{{-- Standardised photo protocol so scans are comparable month to month. --}}
<div {{ isset($class) ? 'class=' . $class : '' }}>
    <h2 class="text-base font-semibold">Take comparable photos</h2>
    <p class="mt-1 text-sm text-slate-400">Small changes in setup look like body changes. Repeat the same setup every month.</p>
    <dl class="mt-4 space-y-3 text-sm">
        @foreach ([
            ['Lighting', 'Bright, even light from the front (a window or ceiling light). No strong light behind you, no flash.'],
            ['Distance', 'Camera 2–3 m away so your whole body fills most of the frame, head to feet.'],
            ['Camera angle', 'Phone upright (portrait) at chest height, held level. Use a timer or ask a friend; no mirror selfies.'],
            ['Pose', 'Relaxed, natural stance. Feet hip-width apart, arms slightly away from the body. Don\'t flex or suck in.'],
            ['Clothing', 'The same fitted gym wear each time (shorts, sports bra or vest).'],
            ['Timing', 'Same time of day, ideally in the morning before eating or training.'],
            ['Background', 'Same plain wall or spot, uncluttered.'],
        ] as [$term, $desc])
            <div class="flex gap-3">
                <dt class="w-24 shrink-0 font-medium text-volt-300">{{ $term }}</dt>
                <dd class="text-slate-300">{{ $desc }}</dd>
            </div>
        @endforeach
    </dl>
</div>
