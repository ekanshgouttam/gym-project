{{-- 11. Contact: details, map link and a form that lands in the owner's Enquiries --}}
@php
    $address = collect([$gym->address, $gym->city])->filter()->implode(', ');
    $err = $errors->getBag('contact');
@endphp
<section id="contact" class="s-section bg-coal-950">
    <div class="s-container grid gap-16 lg:grid-cols-2">
        <div>
            <p class="s-eyebrow" data-reveal-up>Contact us</p>
            <h2 class="s-h2 mt-5" data-split="lines">Come say <span class="text-blaze-500">hi.</span></h2>
            <dl class="mt-12 space-y-8">
                @if ($address)
                    <div data-reveal-up><dt class="s-label">Address</dt><dd class="text-2xl text-white">{{ $address }}</dd>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($gym->name . ', ' . $address) }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.15em] text-blaze-400 hover:text-blaze-300">Get directions →</a></div>
                @endif
                @if ($gym->setting('opening_hours'))<div data-reveal-up><dt class="s-label">Opening hours</dt><dd class="text-lg text-zinc-300">{{ $gym->setting('opening_hours') }}</dd></div>@endif
                <div class="grid gap-8 sm:grid-cols-2" data-reveal-up>
                    @if ($gym->phone)<div><dt class="s-label">Call</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $gym->phone) }}" class="text-lg text-white hover:text-blaze-400">{{ $gym->phone }}</a></dd></div>@endif
                    @if ($gym->email)<div><dt class="s-label">Email</dt><dd><a href="mailto:{{ $gym->email }}" class="break-all text-lg text-white hover:text-blaze-400">{{ $gym->email }}</a></dd></div>@endif
                </div>
            </dl>
        </div>

        <div class="rounded-[2rem] border border-white/[.07] bg-coal-900 p-6 sm:p-10" data-reveal-up>
            @if (session('site_status') && session('site_form') === 'contact')
                <div class="flex min-h-[420px] flex-col items-center justify-center text-center" role="status">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-blaze-500 text-2xl text-white">✓</span>
                    <h3 class="mt-6 text-4xl">Message sent</h3>
                    <p class="mt-3 max-w-sm text-zinc-400">{{ session('site_status') }}</p>
                </div>
            @else
                <form method="POST" action="{{ route('site.enquire') }}" class="space-y-5" novalidate>
                    @csrf
                    <input type="hidden" name="form" value="contact">
                    <div class="hidden" aria-hidden="true"><label>Company <input name="company" tabindex="-1" autocomplete="off"></label></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="c-name" class="s-label">Name</label><input id="c-name" name="name" value="{{ old('form') === 'contact' ? old('name') : '' }}" class="s-input" autocomplete="name" required>@if ($err->has('name'))<p class="s-error">{{ $err->first('name') }}</p>@endif</div>
                        <div><label for="c-phone" class="s-label">Mobile</label><input id="c-phone" name="phone" type="tel" inputmode="tel" value="{{ old('form') === 'contact' ? old('phone') : '' }}" class="s-input" autocomplete="tel" required>@if ($err->has('phone'))<p class="s-error">{{ $err->first('phone') }}</p>@endif</div>
                    </div>
                    <div><label for="c-email" class="s-label">Email <span class="normal-case tracking-normal text-zinc-600">(optional)</span></label><input id="c-email" name="email" type="email" value="{{ old('form') === 'contact' ? old('email') : '' }}" class="s-input" autocomplete="email">@if ($err->has('email'))<p class="s-error">{{ $err->first('email') }}</p>@endif</div>
                    <div>
                        <label for="c-interest" class="s-label">I'm interested in</label>
                        <select id="c-interest" name="interest" class="s-input">
                            <option value="">General enquiry</option>
                            @foreach ($site['programs'] as $p)<option value="{{ $p['name'] }}" {{ old('interest') === $p['name'] ? 'selected' : '' }}>{{ $p['name'] }}</option>@endforeach
                            <option value="Personal training">Personal training</option>
                            <option value="Membership">Membership plans</option>
                        </select>
                    </div>
                    <div><label for="c-message" class="s-label">Message</label><textarea id="c-message" name="message" rows="4" class="s-input">{{ old('form') === 'contact' ? old('message') : '' }}</textarea></div>
                    <button type="submit" class="s-btn-primary w-full" data-magnetic="0.15"><span>Send message</span><span class="s-arrow">→</span></button>
                </form>
            @endif
        </div>
    </div>
</section>
