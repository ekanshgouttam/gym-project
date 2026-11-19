<x-public-layout title="How it works">
    <x-page-hero eyebrow="How it works" title="Live today.<br><span class='text-volt-400'>Members</span> joining tomorrow." lead="Here's exactly what happens for you, and for every member who scans your QR code." />

    @php
        $joinExample = $exampleGym ? $exampleGym->joinUrl() : url('/join/your-gym-name');
        $owner = [
            ['Register your gym', 'Gym name, owner name, email, mobile, password, address and logo. Two minutes.', route('register'), 'Register now'],
            ['Your dashboard is created', 'A separate, private account for your gym, with a free trial already running.', null, null],
            ['Print your QR code', 'Download or print a poster from your panel. Its link looks like ' . $joinExample, null, null],
            ['Set your plans and fees', 'Monthly, quarterly, yearly: your prices, your rules.', null, null],
            ['Approve and manage', 'Approve desk payments, add trainers, track attendance and see who scanned in.', null, null],
        ];
        $member = [
            ['Scan the QR code', 'At the front desk or from your gym\'s public page. It opens your gym\'s branded sign-up.', null, null],
            ['Verify mobile with OTP', 'A 6-digit code confirms the number is theirs.', null, null],
            ['Fill in the profile', 'Name, email, date of birth, height, weight, goal and an emergency contact.', null, null],
            ['Baseline body scan', 'Front, side and back photos become the starting point of their transformation.', null, null],
            ['Choose a plan and pay', 'Pay online to activate instantly, or pay at the desk for staff approval.', null, null],
            ['Use the member app', 'Membership, attendance, workouts, diet, notifications and a new AI scan every 30 days.', route('gyms'), 'Find a gym'],
        ];
    @endphp

    <section class="pb-24">
        <div class="mx-auto max-w-4xl px-4 sm:px-6" data-tabs>
            <div class="mx-auto flex w-fit rounded-full border border-ink-700 bg-ink-900 p-1" role="tablist" aria-label="Choose a view" data-intro>
                <button type="button" role="tab" data-tab="owner" class="rounded-full px-5 py-2 text-sm font-semibold transition">For gym owners</button>
                <button type="button" role="tab" data-tab="member" class="rounded-full px-5 py-2 text-sm font-semibold transition">For members</button>
            </div>

            @foreach (['owner' => $owner, 'member' => $member] as $panel => $steps)
                <ol data-panel="{{ $panel }}" class="mt-12 space-y-4" role="tabpanel" @if ($panel === 'member') hidden @endif>
                    @foreach ($steps as $i => [$title, $text, $link, $label])
                        <li class="card flex gap-5 p-6">
                            <span class="font-display text-3xl font-bold {{ $panel === 'owner' ? 'text-volt-400' : 'text-ember-400' }}">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold">{{ $title }}</h2>
                                <p class="mt-1 break-words text-sm text-slate-400">{{ $text }}</p>
                                @if ($link)<a href="{{ $link }}" class="mt-3 inline-block text-sm font-semibold text-volt-400 hover:text-volt-300">{{ $label }} →</a>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>
    </section>

    <section class="border-t border-ink-700 py-24">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 md:grid-cols-3" data-stagger>
            @foreach ([
                ['30-day scan rule', 'A new body scan unlocks 30 full days after the last one. The server enforces it, and members see a live countdown.'],
                ['Private by design', 'Each gym only sees its own members. Body photos are stored privately, never on a public link.'],
                ['Honest AI', 'Reports describe what\'s visible in photos. They never claim body-fat %, muscle mass or medical results.'],
            ] as [$t, $d])
                <div class="card p-6">
                    <h3 class="text-lg font-semibold">{{ $t }}</h3>
                    <p class="mt-2 text-sm text-slate-400">{{ $d }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-14 flex justify-center gap-3" data-reveal>
            <a href="{{ route('register') }}" class="btn-primary">Register your gym</a>
            <a href="{{ route('demo') }}" class="btn-ghost">Book a demo</a>
        </div>
    </section>
</x-public-layout>
