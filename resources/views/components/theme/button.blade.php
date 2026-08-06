@props([
    'href' => null,
    'variant' => 'navy',
    'type' => 'button',
])

@php
    $variants = [
        'navy' => 'bg-navy text-white hover:bg-navy-mid focus-visible:ring-navy',
        'orange' => 'bg-orange text-navy hover:bg-orange-light focus-visible:ring-orange',
        'outline' => 'border border-line bg-white text-navy hover:border-tosca hover:text-tosca focus-visible:ring-tosca',
    ];

    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 '.$variants[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
