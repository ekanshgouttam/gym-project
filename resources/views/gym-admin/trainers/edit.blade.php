<x-app-layout :title="'Trainer · ' . $trainer->name">
    @php $profile = $trainer->trainerProfile; @endphp
    <form method="POST" action="{{ route('gym-admin.trainers.update', $trainer) }}" class="grid max-w-6xl gap-6 lg:grid-cols-2" novalidate>
        @csrf @method('PUT')
        <section class="card h-fit space-y-5 p-6" data-intro>
            <h2 class="text-base font-semibold">Profile</h2>
            <div><label for="specialty" class="label">Specialty</label><input id="specialty" name="specialty" value="{{ old('specialty', optional($profile)->specialty) }}" class="input" placeholder="e.g. Strength & conditioning"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label for="experience_years" class="label">Years of experience</label><input id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', optional($profile)->experience_years) }}" class="input"></div>
                <div><label for="certifications" class="label">Certifications</label><input id="certifications" name="certifications" value="{{ old('certifications', optional($profile)->certifications) }}" class="input" placeholder="ACE, K11…"></div>
            </div>
            <div><label for="bio" class="label">Bio</label><textarea id="bio" name="bio" rows="4" class="input">{{ old('bio', optional($profile)->bio) }}</textarea></div>
            <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="is_public" value="1" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ old('is_public', optional($profile)->is_public ?? true) ? 'checked' : '' }}> Show on the gym's public page</label>
            <p class="text-xs text-slate-500">Name, email, password and status are edited under <a href="{{ route('manage.users.edit', $trainer) }}" class="text-volt-400">Members → {{ $trainer->name }}</a>.</p>
        </section>

        <section class="card space-y-4 p-6" data-intro>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Assigned members</h2>
                <input type="search" placeholder="Filter" class="input w-40 py-1.5 text-sm" oninput="const q=this.value.toLowerCase();document.querySelectorAll('[data-member-row]').forEach(r=>r.hidden=!r.dataset.memberRow.includes(q))" aria-label="Filter members">
            </div>
            <ul class="max-h-[28rem] space-y-1 overflow-y-auto pr-1">
                @forelse ($members as $m)
                    @php $current = optional($m->memberProfile)->trainer_id; @endphp
                    <li data-member-row="{{ strtolower($m->name . ' ' . optional($m->memberProfile)->member_code) }}">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2 hover:bg-ink-850">
                            <span class="flex items-center gap-3">
                                <input type="checkbox" name="members[]" value="{{ $m->id }}" class="rounded border-ink-600 bg-ink-850 text-volt-500 focus:ring-volt-400" {{ $current === $trainer->id ? 'checked' : '' }}>
                                <span class="text-sm text-white">{{ $m->name }} <span class="text-xs text-slate-500">{{ optional($m->memberProfile)->member_code }}</span></span>
                            </span>
                            @if ($current && $current !== $trainer->id)<span class="text-xs text-slate-500">with {{ optional($m->memberProfile->trainer)->name }}</span>@endif
                        </label>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No active members yet.</li>
                @endforelse
            </ul>
            <p class="text-xs text-slate-500">Ticking a member who is with another trainer moves them here.</p>
        </section>

        <div class="flex justify-end gap-3 lg:col-span-2">
            <a href="{{ route('gym-admin.trainers.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</x-app-layout>
