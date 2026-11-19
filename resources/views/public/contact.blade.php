<x-public-layout title="Contact">
    <x-page-hero eyebrow="Contact" title="Let's talk<br><span class='text-volt-400'>training</span>." lead="Questions about plans, personal training or a free tour? We reply within one working day." />

    <section class="pb-24">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-5">
            <form method="POST" action="{{ route('contact.send') }}" class="card space-y-5 p-6 sm:p-8 lg:col-span-3" data-reveal data-busy="Sending…" novalidate>
                @csrf
                <x-flash />
                <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="input" autocomplete="name" required>
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" autocomplete="email" required>
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="label">Phone <span class="text-slate-500">(optional)</span></label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="input" autocomplete="tel">
                    </div>
                    <div>
                        <label for="gym_id" class="label">Branch</label>
                        <select id="gym_id" name="gym_id" class="input">
                            <option value="">Any branch</option>
                            @foreach ($gyms as $gym)
                                <option value="{{ $gym->id }}" {{ old('gym_id') == $gym->id ? 'selected' : '' }}>{{ $gym->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="subject" class="label">Subject</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" class="input">
                </div>
                <div>
                    <label for="message" class="label">Message</label>
                    <textarea id="message" name="message" rows="5" class="input" required>{{ old('message') }}</textarea>
                    @error('message') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">Send message</button>
            </form>

            <div class="space-y-4 lg:col-span-2" data-stagger>
                @foreach ($gyms as $gym)
                    <div class="card p-6">
                        <h2 class="text-lg font-semibold">{{ $gym->name }}</h2>
                        <p class="mt-1 text-sm text-slate-400">{{ collect([$gym->address, $gym->city])->filter()->implode(', ') }}</p>
                        <dl class="mt-4 space-y-1 text-sm">
                            @if ($gym->phone)<div class="flex gap-2"><dt class="text-slate-500">Phone</dt><dd><a href="tel:{{ preg_replace('/\s+/', '', $gym->phone) }}" class="text-slate-200 hover:text-volt-400">{{ $gym->phone }}</a></dd></div>@endif
                            @if ($gym->email)<div class="flex gap-2"><dt class="text-slate-500">Email</dt><dd><a href="mailto:{{ $gym->email }}" class="text-slate-200 hover:text-volt-400">{{ $gym->email }}</a></dd></div>@endif
                        </dl>
                    </div>
                @endforeach
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">Opening hours</h2>
                    <p class="mt-2 text-sm text-slate-400">Mon–Sat 5:30 am – 11:00 pm<br>Sunday 7:00 am – 1:00 pm</p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
