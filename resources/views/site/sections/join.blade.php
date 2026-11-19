{{-- 12. Join now: free-trial booking, full online registration and the gym's QR code --}}
@php $err = $errors->getBag('join'); @endphp
<section id="join" class="relative overflow-hidden bg-blaze-500 py-24 text-white sm:py-32">
    <div class="pointer-events-none absolute inset-0 opacity-[.12] [background-image:linear-gradient(rgba(0,0,0,1)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,1)_1px,transparent_1px)] [background-size:56px_56px]"></div>
    <p class="pointer-events-none absolute -bottom-10 left-0 select-none whitespace-nowrap font-brand text-[30vw] leading-none text-coal-950/10" aria-hidden="true" data-speed="0.9">JOIN</p>

    <div class="s-container relative grid gap-14 lg:grid-cols-[1.2fr_1fr]">
        <div>
            <p class="inline-flex items-center gap-3 text-[0.72rem] font-semibold uppercase tracking-[0.32em] text-coal-950"><span class="h-px w-8 bg-coal-950"></span>Join now</p>
            <h2 class="mt-6 text-[clamp(3.4rem,8.2vw,8.5rem)] leading-[.88] !text-white" data-split="lines">Your first<br>session<br><span class="text-coal-950">is on us.</span></h2>
            <p class="mt-8 max-w-md text-lg text-white/90">Book a free trial with a coach, or register online in two minutes and start today.</p>
            <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                <a href="{{ $gym->joinUrl() }}" class="s-btn bg-coal-950 text-white hover:bg-white hover:text-coal-950" data-magnetic data-page-link><span>Register online</span><span class="s-arrow">→</span></a>
                <div class="flex items-center gap-4">
                    <div class="h-20 w-20 rounded-xl bg-white p-1.5 [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR code to register at {{ $gym->name }}">{!! app(\App\Services\QrCodeService::class)->svg($gym->qrUrl(), 160) !!}</div>
                    <p class="max-w-[10rem] text-sm text-white/90">At the gym? Scan to register on your phone.</p>
                </div>
            </div>
        </div>

        <div class="rounded-[2rem] bg-coal-950 p-6 text-zinc-300 shadow-2xl sm:p-10">
            @if (session('site_status') && session('site_form') === 'join')
                <div class="flex min-h-[380px] flex-col items-center justify-center text-center" role="status">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-blaze-500 text-2xl text-white">✓</span>
                    <h3 class="mt-6 text-4xl">See you soon</h3>
                    <p class="mt-3 max-w-sm text-zinc-400">{{ session('site_status') }}</p>
                    <a href="{{ $gym->joinUrl() }}" class="s-btn-ghost mt-8" data-page-link><span>Or register now</span></a>
                </div>
            @else
                <h3 class="text-4xl">Book a free trial</h3>
                <p class="mt-2 text-sm text-zinc-500">A coach will call you to fix a time. No payment needed.</p>
                <form method="POST" action="{{ route('site.enquire') }}" class="mt-8 space-y-5" novalidate data-join-form>
                    @csrf
                    <input type="hidden" name="form" value="join">
                    <div class="hidden" aria-hidden="true"><label>Company <input name="company" tabindex="-1" autocomplete="off"></label></div>
                    <div><label for="j-name" class="s-label">Name</label><input id="j-name" name="name" value="{{ old('form') === 'join' ? old('name') : '' }}" class="s-input" autocomplete="name" required>@if ($err->has('name'))<p class="s-error">{{ $err->first('name') }}</p>@endif</div>
                    <div><label for="j-phone" class="s-label">Mobile</label><input id="j-phone" name="phone" type="tel" inputmode="tel" value="{{ old('form') === 'join' ? old('phone') : '' }}" class="s-input" autocomplete="tel" placeholder="98200 00000" required>@if ($err->has('phone'))<p class="s-error">{{ $err->first('phone') }}</p>@endif</div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="j-interest" class="s-label">Program</label>
                            <select id="j-interest" name="interest" class="s-input" data-interest-select>
                                <option value="">Not sure yet</option>
                                @foreach ($site['programs'] as $p)<option value="{{ $p['name'] }}" {{ old('interest') === $p['name'] ? 'selected' : '' }}>{{ $p['name'] }}</option>@endforeach
                                <option value="Personal training" {{ old('interest') === 'Personal training' ? 'selected' : '' }}>Personal training</option>
                            </select>
                        </div>
                        <div>
                            <label for="j-time" class="s-label">Best time</label>
                            <select id="j-time" name="preferred_time" class="s-input">
                                @foreach (['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'] as $v => $l)<option value="{{ $v }}" {{ old('preferred_time') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="s-btn-primary w-full" data-magnetic="0.15"><span>Book my free session</span><span class="s-arrow">→</span></button>
                </form>
            @endif
        </div>
    </div>
</section>
