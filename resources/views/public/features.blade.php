<x-public-layout title="Features" description="Everything FlexFit does for gym owners and members: QR registration, fees, attendance, trainers, workout and diet plans, AI body scans and reports.">
    <x-page-hero eyebrow="Features" title="Everything your gym runs on.<br><span class='text-volt-400'>Nothing</span> it doesn't." lead="One platform for registrations, fees, attendance, coaching and AI body transformation, with a separate, secure account for every gym." />

    @php
        $groups = [
            ['Members & QR registration', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2z', [
                ['Unique QR code per gym', 'Download, print or show it on your public gym page.', false],
                ['Branded sign-up page', 'Your logo and address on the page members land on.', false],
                ['Mobile OTP verification', 'Every member verifies their number before joining.', false],
                ['Automatic Member IDs', 'Unique, sequential IDs created on sign-up.', false],
                ['Manual member registration', 'Add walk-ins from your panel.', false],
                ['CSV bulk import', 'Bring your existing member list across in one go.', false],
            ]],
            ['Plans, fees & payments', 'M3 7h18v10H3zM3 11h18M7 15h3', [
                ['Membership plans', 'Monthly, quarterly or yearly, with your own prices.', false],
                ['Online payments', 'Members pay through Razorpay; access activates instantly.', false],
                ['Desk payments & approvals', 'Approve cash or UPI payments taken at the counter.', false],
                ['Payment reminders', 'Automatic nudges before fees are due.', false],
                ['Expiry notifications', 'Members and staff hear before a membership lapses.', false],
            ]],
            ['Attendance, trainers & coaching', 'M16 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1M9 10a3 3 0 100-6 3 3 0 000 6zM22 19v-1a4 4 0 00-3-3.87M16 4.13a4 4 0 010 7.75', [
                ['Trainer accounts', 'Trainers get their own login and see their members.', false],
                ['Attendance history', 'Members see every visit in their app.', false],
                ['Desk check-in', 'Mark attendance from the owner panel.', false],
                ['Workout & diet plans', 'Members follow their plans in the app.', false],
                ['Plan builder for trainers', 'Create and assign workout and diet plans.', false],
            ]],
            ['AI body transformation', 'M13 2L3 14h9l-1 8 10-12h-9l1-8z', [
                ['Front, side & back scans', 'Photo checks catch dark, blurry or cropped photos.', false],
                ['Baseline + 30-day rhythm', 'First scan is the baseline; new scans unlock every 30 days.', false],
                ['AI visual reports', 'Posture, proportions and visible definition, in plain words.', false],
                ['Before/after slider', 'Matching angles side by side with a drag slider.', false],
                ['Monthly history', 'A timeline of scans, weight and measurements.', false],
                ['Staff corrections, audited', 'Gym staff can fix or void a scan; every change is logged.', false],
            ]],
            ['Enquiries & reports', 'M4 20V10M10 20V4M16 20v-7M22 20H2', [
                ['Owner dashboard', 'Members, sign-ups and growth at a glance.', false],
                ['Enquiry tracking', 'Log walk-in and phone enquiries and track conversions.', false],
                ['Reports', 'Revenue, attendance and retention reports.', false],
            ]],
            ['Built for SaaS', 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z', [
                ['Separate account per gym', 'Owners only ever see their own gym and members.', false],
                ['Private photo storage', 'Body photos are never public; only the member and their gym can open them.', false],
                ['Mobile-first member app', 'Works in any phone browser, no install needed.', false],
            ]],
        ];
    @endphp

    <section class="pb-24">
        <div class="mx-auto max-w-7xl space-y-20 px-4 sm:px-6">
            @foreach ($groups as [$title, $icon, $items])
                <div class="grid gap-8 lg:grid-cols-[18rem_1fr]">
                    <div data-reveal class="lg:sticky lg:top-24 lg:self-start">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-volt-400/10 text-volt-400">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                        </span>
                        <h2 class="mt-4 text-2xl font-bold">{{ $title }}</h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2" data-stagger>
                        @foreach ($items as [$name, $text, $soon])
                            <div class="card p-5 transition hover:border-volt-400/40">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-semibold text-white">{{ $name }}</h3>
                                    @if ($soon)<span class="badge shrink-0 bg-ember-500/15 text-ember-300">Coming soon</span>@endif
                                </div>
                                <p class="mt-1.5 text-sm text-slate-400">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="px-4 pb-24 sm:px-6">
        <div class="card mx-auto flex max-w-5xl flex-col items-center gap-6 p-10 text-center sm:flex-row sm:text-left" data-reveal>
            <div class="flex-1">
                <h2 class="text-2xl font-bold sm:text-3xl">See it with your own gym's data.</h2>
                <p class="mt-2 text-slate-400">A 20-minute walkthrough, tailored to how you run your gym.</p>
            </div>
            <div class="flex shrink-0 gap-3">
                <a href="{{ route('demo') }}" class="btn-primary">Book a demo</a>
                <a href="{{ route('register') }}" class="btn-ghost">Start free</a>
            </div>
        </div>
    </section>
</x-public-layout>
