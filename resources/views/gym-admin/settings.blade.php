<x-app-layout title="Settings">
    <form method="POST" action="{{ route('gym-admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6" novalidate>
        @csrf @method('PUT')

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Gym profile</h2>
            <p class="text-sm text-slate-400">Shown on your <a href="{{ route('gyms.show', $gym) }}" target="_blank" rel="noopener" class="text-volt-400">public gym page</a> and member sign-up page.</p>
            <div class="flex items-center gap-4">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-ink-600 bg-ink-850">
                    @if ($gym->logoUrl())<img id="logo-preview" src="{{ $gym->logoUrl() }}" alt="" class="h-full w-full object-contain p-1">
                    @else<span data-preview-placeholder class="absolute inset-0 grid place-items-center text-xs text-slate-500">Logo</span><img id="logo-preview" alt="" class="hidden h-full w-full object-contain p-1">@endif
                </div>
                <div class="min-w-0 flex-1">
                    <label for="logo" class="label">Logo</label>
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" data-preview="#logo-preview" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                    @if ($gym->logo_path)<label class="mt-2 flex items-center gap-2 text-xs text-slate-400"><input type="checkbox" name="remove_logo" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500"> Remove logo</label>@endif
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="name" class="label">Gym name</label><input id="name" name="name" value="{{ old('name', $gym->name) }}" class="input" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="phone" class="label">Phone</label><input id="phone" name="phone" value="{{ old('phone', $gym->phone) }}" class="input"></div>
                <div><label for="email" class="label">Email</label><input id="email" name="email" type="email" value="{{ old('email', $gym->email) }}" class="input">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="address" class="label">Address</label><input id="address" name="address" value="{{ old('address', $gym->address) }}" class="input"></div>
                <div><label for="city" class="label">City</label><input id="city" name="city" value="{{ old('city', $gym->city) }}" class="input"></div>
                <div class="sm:col-span-2"><label for="opening_hours" class="label">Opening hours</label><input id="opening_hours" name="opening_hours" value="{{ old('opening_hours', $gym->setting('opening_hours')) }}" class="input" placeholder="Mon–Sat 5:30am–10:30pm · Sun 7am–12pm"></div>
                <div class="sm:col-span-2"><label for="about" class="label">About your gym</label><textarea id="about" name="about" rows="3" class="input" placeholder="Equipment, specialties, what makes you different.">{{ old('about', $gym->setting('about')) }}</textarea></div>
            </div>
        </section>

        <section class="card space-y-4 p-6" data-intro>
            <h2 class="text-base font-semibold">Reminders</h2>
            <label class="flex items-start gap-3 text-sm text-slate-300"><input type="checkbox" name="expiry_reminders" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ $gym->setting('expiry_reminders') ? 'checked' : '' }}> <span>Remind members before their membership ends, and when it has ended</span></label>
            <fieldset class="ml-7">
                <legend class="text-sm text-slate-400">Remind them</legend>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach ([15, 7, 3, 1] as $d)
                        <label class="flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-850 px-3 py-1.5 text-sm text-slate-300 has-[:checked]:border-volt-400"><input type="checkbox" name="reminder_days[]" value="{{ $d }}" class="rounded border-ink-600 bg-ink-850 text-volt-500" {{ in_array($d, (array) $gym->setting('reminder_days')) ? 'checked' : '' }}> {{ $d }} {{ $d === 1 ? 'day' : 'days' }} before</label>
                    @endforeach
                </div>
            </fieldset>
            <label class="flex items-start gap-3 text-sm text-slate-300"><input type="checkbox" name="payment_reminders" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ $gym->setting('payment_reminders') ? 'checked' : '' }}> <span>Remind members whose membership request is unpaid after 2 days</span></label>
            <label class="flex items-start gap-3 text-sm text-slate-300"><input type="checkbox" name="notify_owner" value="1" class="mt-0.5 rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ $gym->setting('notify_owner') ? 'checked' : '' }}> <span>Send me a daily summary of reminders sent</span></label>
            <p class="text-xs text-slate-500">Reminders go out every morning at 9:00 as in-app notifications.</p>
        </section>

        @php
            $media = $gym->setting('site_media') ?: [];
            $isSiteGym = optional(\App\Http\Controllers\SiteController::gym())->id === $gym->id;
        @endphp
        <section class="card space-y-5 p-6" data-intro>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Website</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        @if ($isSiteGym)
                            Your photos and video replace the default ones on the <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-volt-400">gym website</a>.
                        @else
                            Saved for your gym. They appear on the premium website when FlexFit sets it to show your gym.
                        @endif
                    </p>
                </div>
                @if ($isSiteGym)<a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-ghost">View website</a>@endif
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="hero_video" class="label">Hero video <span class="text-slate-500">(MP4, up to 25 MB)</span></label>
                    <input id="hero_video" name="hero_video" type="file" accept="video/mp4" data-max-mb="25" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                    <p class="mt-1 text-xs text-slate-500">10–20 seconds, landscape, no sound needed. It plays muted in a loop and is shown in black and white.</p>
                    @error('hero_video')<p class="field-error">{{ $message }}</p>@enderror
                    @if (! empty($media['hero_video']))
                        <div class="mt-3 flex items-center gap-3">
                            <video src="{{ route('site.media', [$gym, $media['hero_video']]) }}" class="h-16 w-28 rounded-lg object-cover" muted preload="metadata"></video>
                            <label class="flex items-center gap-2 text-xs text-slate-400"><input type="checkbox" name="remove_hero_video" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500"> Remove</label>
                        </div>
                    @endif
                </div>
                <div>
                    <label for="hero_image" class="label">Hero photo <span class="text-slate-500">(shown before the video loads)</span></label>
                    <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp" data-max-mb="10" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                    @error('hero_image')<p class="field-error">{{ $message }}</p>@enderror
                    @if (! empty($media['hero_image']))
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ route('site.media', [$gym, $media['hero_image']]) }}" alt="" class="h-16 w-28 rounded-lg object-cover">
                            <label class="flex items-center gap-2 text-xs text-slate-400"><input type="checkbox" name="remove_hero_image" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500"> Remove</label>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label for="gallery" class="label">Gallery photos <span class="text-slate-500">(up to 9, shown first in the gallery)</span></label>
                <input id="gallery" name="gallery[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                @error('gallery')<p class="field-error">{{ $message }}</p>@enderror
                @error('gallery.*')<p class="field-error">{{ $message }}</p>@enderror
                @if (! empty($media['gallery']))
                    <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($media['gallery'] as $file)
                            <li>
                                <img src="{{ route('site.media', [$gym, $file]) }}" alt="" class="aspect-square w-full rounded-lg object-cover">
                                <label class="mt-1 flex items-center gap-1.5 text-xs text-slate-400"><input type="checkbox" name="remove_gallery[]" value="{{ $file }}" class="rounded border-ink-600 bg-ink-850 text-volt-500"> Remove</label>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <div class="flex justify-end"><button type="submit" class="btn-primary">Save settings</button></div>
    </form>
</x-app-layout>
