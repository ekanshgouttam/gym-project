<x-app-layout title="Branding">
    <form method="POST" action="{{ route('gym-admin.branding.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6" novalidate>
        @csrf @method('PUT')

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Logo</h2>
            <p class="text-sm text-slate-400">Shown in the dashboard for you, your trainers and members, in the mobile app, and on your public gym page.</p>
            <div class="flex items-center gap-4">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-ink-600 bg-ink-850">
                    @if ($gym->logoUrl())<img id="logo-preview" src="{{ $gym->logoUrl() }}" alt="" class="h-full w-full object-contain p-1">
                    @else<span data-preview-placeholder class="absolute inset-0 grid place-items-center text-xs text-slate-500">Logo</span><img id="logo-preview" alt="" class="hidden h-full w-full object-contain p-1">@endif
                </div>
                <div class="min-w-0 flex-1">
                    <label for="logo" class="label">Upload logo</label>
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" data-preview="#logo-preview" class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-700 file:px-3 file:py-2 file:text-sm file:text-white">
                    <p class="mt-1.5 text-xs text-slate-500">Square PNG with a transparent background works best.</p>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                    @if ($gym->logo_path)<label class="mt-2 flex items-center gap-2 text-xs text-slate-400"><input type="checkbox" name="remove_logo" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500"> Remove logo</label>@endif
                </div>
            </div>
        </section>

        <section class="card space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Colours</h2>
            <p class="text-sm text-slate-400">If a colour would be hard to read on the chosen theme, it is automatically made a little lighter or darker.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="primary" class="label">Primary colour</label>
                    <div class="flex items-center gap-3">
                        <input id="primary" name="primary" type="color" value="{{ old('primary', $brand['primary']) }}" class="h-11 w-16 cursor-pointer rounded-lg border border-ink-600 bg-ink-850 p-1">
                        <span class="text-sm text-slate-400">Buttons, highlights, active menu items, charts</span>
                    </div>
                    @error('primary')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="accent" class="label">Accent colour</label>
                    <div class="flex items-center gap-3">
                        <input id="accent" name="accent" type="color" value="{{ old('accent', $brand['accent']) }}" class="h-11 w-16 cursor-pointer rounded-lg border border-ink-600 bg-ink-850 p-1">
                        <span class="text-sm text-slate-400">Secondary buttons, targets, second chart line</span>
                    </div>
                    @error('accent')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card space-y-4 p-6" data-intro>
            <h2 class="text-base font-semibold">Theme</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (\App\Support\Branding::THEMES as $value => $label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-600 bg-ink-850 p-4 has-[:checked]:border-volt-400">
                        <input type="radio" name="theme" value="{{ $value }}" class="border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('theme', $brand['theme']) === $value ? 'checked' : '' }}>
                        <span class="grid h-8 w-12 place-items-center rounded-md border {{ $value === 'light' ? 'border-slate-300 bg-[#F3F5F8]' : 'border-slate-700 bg-[#080B11]' }}"><span class="h-2 w-6 rounded-full" style="background: {{ $brand['primary'] }}"></span></span>
                        <span class="text-sm font-medium text-white">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('theme')<p class="field-error">{{ $message }}</p>@enderror
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button class="btn-primary">Save branding</button>
            <button name="reset" value="1" class="btn-ghost">Reset colours to default</button>
        </div>
    </form>
</x-app-layout>
