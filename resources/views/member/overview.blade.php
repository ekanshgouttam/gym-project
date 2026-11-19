<x-app-layout title="Overview">
    @php
        $bmi = $stats->bmi();
        $change = $stats->weightChange();
    @endphp

    {{-- Welcome + membership --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card relative overflow-hidden p-6 sm:p-8 xl:col-span-2" data-intro>
            <div class="grid-bg absolute inset-0 opacity-50"></div>
            <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-volt-400/15 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-volt-400/40">
                @endif
                <div class="flex-1">
                    <p class="text-sm text-slate-400">{{ now()->format('l, d M') }}</p>
                    <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Hi {{ \Illuminate\Support\Str::before($user->name . ' ', ' ') }}, let's get to work.</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Member ID <span class="font-mono text-slate-200">{{ optional($profile)->member_code ?? '—' }}</span>
                        · {{ optional($user->gym)->name }}
                        @if ($profile && $profile->fitness_goal) · Goal: {{ $profile->goalLabel() }} @endif
                    </p>
                </div>
            </div>
        </section>

        <section class="card p-6" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Membership</h2>
                <a href="{{ route('member.membership') }}" class="text-sm text-volt-400 hover:text-volt-300">Manage</a>
            </div>
            @if ($membership)
                <p class="mt-4 font-display text-2xl font-bold">{{ $membership->plan->name }}</p>
                <p class="text-sm text-slate-400">Valid until {{ $membership->ends_on->format('d M Y') }}</p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-ink-700" role="progressbar" aria-valuenow="{{ $membership->progressPercent() }}" aria-valuemin="0" aria-valuemax="100" aria-label="Membership used">
                    <div class="h-full rounded-full bg-volt-400" style="width: {{ $membership->progressPercent() }}%"></div>
                </div>
                <p class="mt-2 text-sm {{ $membership->daysLeft() <= 7 ? 'text-amber-300' : 'text-slate-400' }}">{{ $membership->daysLeft() }} days left</p>
            @elseif ($pending)
                <span class="badge mt-4 bg-amber-500/15 text-amber-300">Awaiting activation</span>
                <p class="mt-3 text-sm text-slate-400">{{ $pending->plan->name }} · pay online or at the front desk to activate.</p>
                <a href="{{ route('member.membership') }}" class="btn-primary mt-4 w-full">Activate now</a>
            @else
                <p class="mt-4 text-sm text-slate-400">You don't have an active membership.</p>
                <a href="{{ route('member.membership') }}" class="btn-primary mt-4 w-full">Choose a plan</a>
            @endif
        </section>
    </div>

    {{-- Key stats --}}
    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">Current weight</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ $stats->currentWeight() ? number_format($stats->currentWeight(), 1) : '—' }}<span class="text-base font-medium text-slate-500"> kg</span></p>
            @if ($change !== null)
                <p class="mt-1 text-xs {{ $change <= 0 ? 'text-emerald-300' : 'text-amber-300' }}">{{ $change > 0 ? '+' : '' }}{{ $change }} kg since joining</p>
            @endif
        </div>
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">BMI</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ $bmi ?? '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\MemberStats::bmiLabel($bmi) }}</p>
        </div>
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">Visits this month</p>
            <p class="mt-2 font-display text-3xl font-bold" data-count="{{ $stats->visitsThisMonth() }}">{{ $stats->visitsThisMonth() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats->weekStreak() }}-week streak</p>
        </div>
        <div class="card p-5" data-intro>
            <p class="text-sm text-slate-400">AI visual score</p>
            <p class="mt-2 font-display text-3xl font-bold">{{ $latestScan ? $latestScan->physique_score : '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $latestScan ? 'Latest scan · ' . $latestScan->created_at->format('d M') : 'No analysed scans yet' }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Weight trend --}}
        <section class="card p-5 xl:col-span-2" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Weight trend</h2>
                <a href="{{ route('member.weight') }}" class="text-sm text-volt-400 hover:text-volt-300">Log weight</a>
            </div>
            <div class="mt-2" data-chart="area" data-height="240" data-series='@json($weightSeries)'></div>
        </section>

        {{-- Scan eligibility --}}
        <section class="card relative overflow-hidden p-6" data-intro>
            <div class="absolute -bottom-10 -right-10 h-40 w-40 rounded-full {{ $scanEligibility['eligible'] ? 'bg-volt-400/20' : 'bg-sky-500/10' }} blur-2xl"></div>
            <h2 class="relative text-base font-semibold">AI body scan</h2>
            @if ($scanEligibility['eligible'])
                <p class="relative mt-4 font-display text-2xl font-bold text-volt-400">Ready now</p>
                <p class="relative mt-1 text-sm text-slate-400">Your next scan is available. Track how your body has changed.</p>
                <a href="{{ route('member.scanner') }}" class="btn-primary relative mt-5 w-full">Start scan</a>
            @elseif ($scanEligibility['next_at'])
                <p class="relative mt-4 font-display text-4xl font-bold">{{ (int) ceil($scanEligibility['seconds_left'] / 86400) }}<span class="text-base font-medium text-slate-400"> days</span></p>
                <p class="relative mt-1 text-sm text-slate-400">Next scan on {{ $scanEligibility['next_at']->format('d M Y') }}</p>
                <a href="{{ route('member.transformation') }}" class="btn-ghost relative mt-5 w-full">View transformation</a>
            @else
                <p class="relative mt-4 text-sm text-slate-400">{{ $scanEligibility['reason'] }}</p>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Today's workout --}}
        <section class="card p-6" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Today's workout</h2>
                <a href="{{ route('member.workouts') }}" class="text-sm text-volt-400 hover:text-volt-300">Full plan</a>
            </div>
            @if ($todayWorkout)
                <p class="mt-4 text-lg font-semibold text-white">{{ $todayWorkout['focus'] ?? 'Workout' }}</p>
                <p class="text-xs text-slate-500">{{ $todayWorkout['plan'] }}</p>
                <ul class="mt-4 divide-y divide-ink-700">
                    @forelse (array_slice($todayWorkout['exercises'] ?? [], 0, 5) as $ex)
                        <li class="flex justify-between py-2 text-sm"><span class="text-slate-200">{{ $ex['name'] }}</span><span class="text-slate-500">{{ $ex['sets'] ?? '' }} × {{ $ex['reps'] ?? '' }}</span></li>
                    @empty
                        <li class="py-2 text-sm text-slate-400">Rest day. Light walk and stretching recommended.</li>
                    @endforelse
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-400">Your trainer hasn't assigned a workout plan yet.</p>
            @endif
        </section>

        {{-- Notifications --}}
        <section class="card p-6" data-intro>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Notifications</h2>
                <a href="{{ route('member.notifications') }}" class="text-sm text-volt-400 hover:text-volt-300">View all</a>
            </div>
            <ul class="mt-4 space-y-3">
                @forelse ($notifications as $n)
                    <li>
                        <a href="{{ route('member.notifications.read', $n->id) }}" class="block rounded-xl border border-ink-700 p-3 hover:border-ink-600">
                            <p class="text-sm font-medium text-white">{{ $n->data['title'] ?? 'Notification' }}</p>
                            <p class="text-xs text-slate-400">{{ $n->data['body'] ?? '' }}</p>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">You're all caught up.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
