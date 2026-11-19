<x-app-layout title="Reports">
    <x-slot name="actions">
        <a href="{{ route('gym-admin.reports.members') }}" class="btn-ghost">Export members</a>
        <a href="{{ route('gym-admin.payments.export') }}" class="btn-ghost">Export this month's payments</a>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Fees collected, last 30 days" :value="$kpis['revenue30']" prefix="₹" />
        <x-stat-card label="Active members" :value="$kpis['active']" :hint="$kpis['inactive'] . ' registered but not active'" />
        <x-stat-card label="Renewal rate" :value="$kpis['renewal'] ?? 0" :hint="$kpis['renewal'] === null ? 'no memberships ended in 90 days' : '% of memberships ending in 90 days that renewed'" />
        <x-stat-card label="Visits, last 30 days" :value="$kpis['visits30']" />
        <x-stat-card label="Enquiry conversion" :value="$kpis['enquiryRate'] ?? 0" :hint="$kpis['enquiryRate'] === null ? 'no enquiries in 90 days' : '% joined, last 90 days'" />
        <x-stat-card label="Members to win back" :value="$atRisk->count()" hint="active but no visit in 14 days" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">Fees collected, last 12 months</h2>
            <div class="mt-4" data-chart="area" data-height="260" data-series='@json($revenueChart)'></div>
        </section>
        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">New members and visits, last 12 months</h2>
            <div class="mt-4" data-chart="line" data-height="260" data-series='@json($growthChart)'></div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">Plans sold (12 months)</h2>
            @php $maxN = max(1, $plans->max('n') ?: 1); @endphp
            <ul class="mt-4 space-y-3">
                @forelse ($plans as $row)
                    <li>
                        <div class="flex justify-between text-sm"><span class="text-slate-300">{{ optional($row->plan)->name }}</span><span class="text-white">{{ $row->n }} · ₹{{ number_format((float) $row->revenue) }}</span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full bg-volt-400" style="width: {{ round($row->n / $maxN * 100) }}%"></div></div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No memberships yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5" data-intro>
            <h2 class="text-base font-semibold">Busiest days (60 days)</h2>
            @php $maxD = max(1, $busiest->max() ?: 1); $names = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun']; @endphp
            <div class="mt-4 flex h-40 items-end gap-2">
                @foreach ($names as $dow => $label)
                    @php $n = (int) ($busiest[$dow] ?? 0); @endphp
                    <div class="flex flex-1 flex-col items-center gap-2" title="{{ $label }}: {{ $n }} visits">
                        <div class="flex w-full flex-1 items-end"><span data-bar="{{ max(3, round($n / $maxD * 100)) }}" class="w-full rounded-t-md bg-gradient-to-t from-ember-500/50 to-ember-400" style="height: {{ max(3, round($n / $maxD * 100)) }}%"></span></div>
                        <span class="text-xs text-slate-500">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card overflow-hidden" data-intro>
            <h2 class="p-5 text-base font-semibold">Win them back</h2>
            <ul class="divide-y divide-ink-700 border-t border-ink-700">
                @forelse ($atRisk as $m)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-white">{{ $m->name }}</span>
                        @if ($m->phone)<a href="https://wa.me/{{ ltrim($m->phone, '+') }}" target="_blank" rel="noopener" class="text-volt-400 hover:text-volt-300">WhatsApp</a>@endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-500">Everyone active has visited in the last 2 weeks.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
