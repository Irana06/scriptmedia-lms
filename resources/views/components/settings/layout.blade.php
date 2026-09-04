<div class="grid overflow-hidden rounded-card border border-line bg-white shadow-[0_16px_40px_-28px_rgba(11,37,69,0.42)] md:grid-cols-[220px_minmax(0,1fr)]">
    <div class="w-full border-b border-line bg-offwhite/70 p-4 md:border-b-0 md:border-e md:p-5">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <div class="min-w-0 p-5 sm:p-7 lg:p-8">
        <flux:heading size="lg">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading class="mt-1">{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-6 w-full max-w-xl">
            {{ $slot }}
        </div>
    </div>
</div>
