<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-settings.layout :heading="__('Appearance')" subheading="Tampilan dioptimalkan agar seluruh informasi tetap jelas dan mudah dibaca.">
        <div class="flex items-start gap-4 rounded-card border border-tosca/25 bg-tosca-tint p-5 text-navy">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-orange shadow-sm">
                <flux:icon.sun class="size-6" />
            </span>
            <div>
                <p class="font-semibold text-navy">Mode terang aktif</p>
                <p class="mt-1 text-sm leading-6 text-ink-soft">Tema ini digunakan secara konsisten pada halaman admin, guru, dan siswa untuk menjaga kontras teks, formulir, serta tombol.</p>
            </div>
        </div>
    </x-settings.layout>
</section>
