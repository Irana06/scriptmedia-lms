<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <x-theme.section-header
            eyebrow="Keamanan data"
            title="Cadangan data"
            description="Cadangan otomatis dibuat setiap malam (data) dan setiap Minggu (data + berkas unggahan). Simpan salinannya di luar server secara berkala."
        />
        <x-theme.button wire:click="createBackup" wire:loading.attr="disabled" wire:target="createBackup">
            <flux:icon.arrow-down-on-square-stack class="size-4" />
            <span wire:loading.remove wire:target="createBackup">Buat cadangan sekarang</span>
            <span wire:loading wire:target="createBackup">Membuat cadangan...</span>
        </x-theme.button>
    </div>

    @if (session('backup_status'))
        <div class="mt-6 rounded-card border border-tosca/40 bg-tosca-tint/60 px-5 py-4 text-sm text-navy" role="status">{{ session('backup_status') }}</div>
    @endif

    <x-theme.card :padding="false" class="mt-6 overflow-hidden">
        <div class="divide-y divide-line">
            @forelse ($backups as $backup)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca-ink"><flux:icon.archive-box class="size-5" /></span>
                        <div>
                            <p class="font-semibold text-navy">{{ $backup['created_at']->translatedFormat('l, d F Y · H:i') }}</p>
                            <p class="text-xs text-ink-soft">{{ str_ends_with($backup['name'], '-lengkap.zip') ? 'Data + berkas unggahan' : 'Data saja' }} · {{ number_format($backup['size'] / 1048576, 2, ',', '.') }} MB</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-theme.button variant="outline" :href="route('admin.backups.download', $backup['name'])"><flux:icon.arrow-down-tray class="size-4" />Unduh</x-theme.button>
                        <button type="button" wire:click="deleteBackup('{{ $backup['name'] }}')" wire:confirm="Hapus cadangan ini?" class="rounded-xl px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <flux:icon.archive-box class="mx-auto size-8 text-ink-soft/60" />
                    <p class="mt-2 text-sm text-ink-soft">Belum ada cadangan. Klik <strong>Buat cadangan sekarang</strong>.</p>
                </div>
            @endforelse
        </div>
    </x-theme.card>

    <p class="mt-4 text-xs text-ink-soft">Pemulihan dilakukan oleh pihak teknis lewat perintah <code class="rounded bg-offwhite px-1.5 py-0.5">php artisan sekolah:restore nama-berkas.zip</code>, karena menimpa seluruh data yang ada.</p>
</div>
