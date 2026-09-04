@props([
    'title',
    'description',
])

<div class="flex min-w-0 w-full flex-col text-center">
    <flux:heading size="xl" class="text-balance">{{ $title }}</flux:heading>
    <flux:subheading class="text-pretty">{{ $description }}</flux:subheading>
</div>
