@props(['role'])
@php
    $styles = [
        'super_admin' => 'bg-fuchsia-500/15 text-fuchsia-300',
        'gym_admin' => 'bg-sky-500/15 text-sky-300',
        'trainer' => 'bg-amber-500/15 text-amber-300',
        'member' => 'bg-volt-400/15 text-volt-300',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge ' . ($styles[$role] ?? 'bg-slate-500/15 text-slate-300')]) }}>{{ \App\Support\Roles::label($role) }}</span>
