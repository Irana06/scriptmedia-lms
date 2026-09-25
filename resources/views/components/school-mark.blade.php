@props([
    'icon' => 'academic-cap',
    'iconClass' => 'size-6',
    'tone' => 'bg-orange text-navy',
])

@php($school = \App\Models\SchoolProfile::current())

@if ($school->logoUrl())
    <span {{ $attributes->class('flex shrink-0 items-center justify-center overflow-hidden bg-white') }}>
        <img src="{{ $school->logoUrl() }}" alt="Logo {{ $school->name }}" class="size-full object-contain p-1">
    </span>
@else
    <span {{ $attributes->class('flex shrink-0 items-center justify-center '.$tone) }}>
        <flux:icon :name="$icon" :class="$iconClass" />
    </span>
@endif
