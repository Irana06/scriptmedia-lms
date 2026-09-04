@props([
    'tone' => 'tosca',
])

@php
    $tones = [
        'tosca' => 'bg-tosca text-navy',
        'tosca-soft' => 'bg-tosca-tint text-navy-mid',
        'orange' => 'bg-orange text-navy',
        'navy' => 'bg-navy text-white',
        'danger' => 'bg-red-100 text-red-700',
        'neutral' => 'bg-offwhite text-ink-soft',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex min-h-6 items-center gap-1.5 rounded-full border border-transparent px-3 py-1 text-xs font-semibold leading-none',
    $tones[$tone] ?? $tones['tosca'],
]) }}>
    {{ $slot }}
</span>
