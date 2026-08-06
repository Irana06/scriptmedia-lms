@props([
    'tone' => 'tosca',
])

@php
    $tones = [
        'tosca' => 'bg-tosca text-white',
        'tosca-soft' => 'bg-tosca-tint text-navy-mid',
        'orange' => 'bg-orange text-navy',
        'navy' => 'bg-navy text-white',
        'neutral' => 'bg-offwhite text-ink-soft',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold leading-none',
    $tones[$tone] ?? $tones['tosca'],
]) }}>
    {{ $slot }}
</span>
