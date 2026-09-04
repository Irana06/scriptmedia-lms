@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'rounded-card border border-line bg-white shadow-[0_16px_40px_-28px_rgba(11,37,69,0.42)]',
    'p-5 sm:p-6' => $padding,
]) }}>
    {{ $slot }}
</div>
