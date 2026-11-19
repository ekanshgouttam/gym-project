<x-public-layout title="Membership plans">
    <x-page-hero eyebrow="Membership plans" title="Commit longer,<br><span class='text-volt-400'>pay less</span>." lead="Every plan includes trainer support, the member portal and AI body scans. Pay online or at the front desk." />

    <section class="pb-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            @if ($plans->isEmpty())
                <p class="text-center text-slate-500">Plans are being updated. Please check back soon.</p>
            @else
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4" data-stagger>
                    @foreach ($plans as $plan)
                        <x-plan-card :plan="$plan" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="border-t border-ink-700 py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-3xl font-bold" data-reveal>Common questions</h2>
            <div class="mt-8 divide-y divide-ink-700 border-y border-ink-700" data-stagger>
                @foreach ([
                    ['How do I join?', 'Scan the QR code at any branch or use Join Gym online. Verify your mobile number, fill in your details and pick a plan.'],
                    ['When does my membership start?', 'Immediately after online payment, or as soon as the front desk approves it if you pay in person.'],
                    ['Can I renew early?', 'Yes. A renewal starts the day after your current membership ends, so you never lose days.'],
                    ['How often can I do an AI body scan?', 'Your baseline scan happens when you join. After that, once every 30 days while your membership is active, so changes are visible.'],
                ] as [$q, $a])
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-medium text-white">
                            {{ $q }}
                            <span class="text-volt-400 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 text-slate-400">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
