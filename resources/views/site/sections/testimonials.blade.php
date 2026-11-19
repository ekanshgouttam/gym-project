{{-- 10. Testimonials: two counter-moving rails; pause on hover, drag on touch --}}
@php $rows = collect($site['testimonials'])->chunk((int) ceil(count($site['testimonials']) / 2)); @endphp
<section id="testimonials" class="overflow-hidden border-y border-white/[.06] bg-coal-900 py-24 sm:py-32" aria-labelledby="testimonials-title">
    <div class="s-container mb-14">
        <p class="s-eyebrow" data-reveal-up>Testimonials</p>
        <h2 id="testimonials-title" class="s-h2 mt-5" data-split="lines">Heard on <span class="text-blaze-500">the floor.</span></h2>
    </div>
    @foreach ($rows as $i => $row)
        <div class="mt-5 overflow-hidden" data-rail="{{ $i % 2 ? 'right' : 'left' }}">
            <ul class="flex w-max gap-5">
                @for ($copy = 0; $copy < 2; $copy++)
                    @foreach ($row as $t)
                        <li class="w-[82vw] shrink-0 rounded-[1.75rem] border border-white/[.07] bg-coal-950 p-8 sm:w-[30rem]" @if ($copy) aria-hidden="true" @endif>
                            <p class="text-blaze-500" aria-label="5 out of 5 stars">★★★★★</p>
                            <blockquote class="mt-5 text-xl leading-snug text-white">“{{ $t['text'] }}”</blockquote>
                            <p class="mt-6 text-sm text-zinc-500"><span class="font-semibold text-zinc-300">{{ $t['name'] }}</span> · {{ $t['role'] }}</p>
                        </li>
                    @endforeach
                @endfor
            </ul>
        </div>
    @endforeach
</section>
