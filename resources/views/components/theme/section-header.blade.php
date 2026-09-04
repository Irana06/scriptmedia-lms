@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
])

<div {{ $attributes->class([
    'max-w-2xl',
    'mx-auto text-center' => $align === 'center',
]) }}>
    @if ($eyebrow)
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-tosca-ink">{{ $eyebrow }}</p>
    @endif

    <h2 class="text-2xl leading-tight sm:text-3xl">{{ $title }}</h2>

    @if ($description)
        <p class="mt-2 text-sm leading-6 text-ink-soft sm:text-base">{{ $description }}</p>
    @endif
</div>
