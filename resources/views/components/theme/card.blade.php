@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'rounded-card border border-line bg-white shadow-[0_14px_34px_-24px_rgba(11,37,69,0.35)]',
    'p-5 sm:p-6' => $padding,
]) }}>
    {{ $slot }}
</div>
