<x-app-layout :title="'Registration QR · ' . $gym->name">
    <div class="grid gap-6 lg:grid-cols-[auto_1fr]">
        <div class="card p-6 print:border-0" id="qr-poster">
            <div class="mx-auto w-72 rounded-2xl bg-white p-4 sm:w-80">
                <div class="[&>svg]:h-auto [&>svg]:w-full">{!! $svg !!}</div>
            </div>
            @if ($gym->logoUrl())<img src="{{ $gym->logoUrl() }}" alt="" class="mx-auto mt-4 h-12 w-12 object-contain">@endif
            <p class="mt-4 text-center font-display text-xl font-bold">Scan to join {{ $gym->name }}</p>
            <p class="mt-1 text-center text-sm text-slate-400">Register in 2 minutes with your phone</p>
        </div>

        <div class="space-y-6">
            <section class="card p-6">
                <h2 class="text-base font-semibold">Registration link</h2>
                <p class="mt-1 text-sm text-slate-400">This QR code opens the mobile registration page for this branch.</p>
                <input readonly value="{{ $gym->joinUrl() }}" class="input mt-4 font-mono text-xs" onclick="this.select()" aria-label="Registration link">
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('manage.gyms.qr.download', $gym) }}" class="btn-primary">Download SVG</a>
                    <button type="button" onclick="window.print()" class="btn-ghost">Print poster</button>
                    <a href="{{ $gym->joinUrl() }}" target="_blank" rel="noopener" class="btn-ghost">Open sign-up page</a>
                    @if ($gym->is_active)<a href="{{ route('gyms.show', $gym) }}" target="_blank" rel="noopener" class="btn-ghost">Public gym page</a>@endif
                </div>
            </section>
            <section class="card p-6">
                <h2 class="text-base font-semibold">Replace QR code</h2>
                <p class="mt-1 text-sm text-slate-400">Generates a new link. Every printed copy of the current code stops working, so only do this if a code was misused.</p>
                <form method="POST" action="{{ route('manage.gyms.qr.rotate', $gym) }}" class="mt-4" data-confirm="Generate a new QR code? All printed copies of the current one will stop working.">
                    @csrf
                    <button class="btn-danger">Generate new code</button>
                </form>
            </section>
        </div>
    </div>

    {{-- QR registration tracking --}}
    <h2 class="mt-10 text-lg font-semibold" data-intro>QR registrations</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="QR scans, last 30 days" :value="$qrStats['scans30']" hint="one per phone visit" />
        <x-stat-card label="Joined via QR, last 30 days" :value="$qrStats['regs30']" />
        <x-stat-card label="Scan → member" :value="$qrStats['conversion'] ?? 0" :hint="$qrStats['conversion'] === null ? 'no scans yet' : '% of scans that registered'" />
        <x-stat-card label="Link visits, last 30 days" :value="$qrStats['linkVisits30']" hint="opened from a shared link" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="card p-5" data-intro>
            <h3 class="text-base font-semibold">Last 6 months</h3>
            <div class="mt-4" data-chart="area" data-height="260" data-series='@json($qrChart)'></div>
        </section>
        <section class="card p-5" data-intro>
            <h3 class="text-base font-semibold">How members joined (all time)</h3>
            @php $total = max(1, $bySource->sum()); @endphp
            <ul class="mt-4 space-y-3">
                @foreach (\App\Models\MemberProfile::SOURCES as $key => $label)
                    @php $n = (int) ($bySource[$key] ?? 0); @endphp
                    <li>
                        <div class="flex justify-between text-sm"><span class="text-slate-300">{{ $label }}</span><span class="text-white">{{ $n }}</span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full {{ $key === 'qr' ? 'bg-volt-400' : ($key === 'link' ? 'bg-sky-400' : ($key === 'manual' ? 'bg-ember-500' : 'bg-fuchsia-400')) }}" style="width: {{ round($n / $total * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden" data-intro>
        <h3 class="p-5 text-base font-semibold">Latest members who joined by QR</h3>
        <ul class="divide-y divide-ink-700">
            @forelse ($recentQr as $p)
                <li class="flex items-center justify-between px-5 py-3 text-sm">
                    <span><span class="text-white">{{ optional($p->user)->name }}</span> <span class="text-slate-500">· {{ $p->member_code }}</span></span>
                    <span class="text-slate-500">{{ $p->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">No QR registrations yet. Print the poster and put it at the front desk.</li>
            @endforelse
        </ul>
    </section>
    <p class="mt-3 text-xs text-slate-500">QR codes downloaded before this update still work, but scans from them count as link visits. Download or print the code again to track scans.</p>

    <style>
        @media print {
            body * { visibility: hidden; }
            #qr-poster, #qr-poster * { visibility: visible; color: #000 !important; }
            #qr-poster { position: absolute; inset: 0; background: #fff !important; }
        }
    </style>
</x-app-layout>
