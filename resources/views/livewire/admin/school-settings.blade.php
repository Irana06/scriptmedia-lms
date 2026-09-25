<div>
    <x-theme.section-header
        eyebrow="Identitas sekolah"
        title="Profil sekolah"
        description="Nama, logo, dan kepala sekolah tampil di halaman masuk, header, dan kop rapor."
    />

    @if (session('school_status'))
        <div class="mt-6 rounded-card border border-tosca/40 bg-tosca-tint/60 px-5 py-4 text-sm text-navy" role="status">{{ session('school_status') }}</div>
    @endif

    <form wire:submit="save" class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
        <x-theme.card>
            <h2 class="text-lg">Data sekolah</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-navy sm:col-span-2">Nama sekolah<input wire:model="name" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" placeholder="SMP Harapan Bangsa" /></label>
                @error('name') <p class="text-sm text-red-600 sm:col-span-2">{{ $message }}</p> @enderror
                <label class="block text-sm font-semibold text-navy">NPSN<input wire:model="npsn" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                <label class="block text-sm font-semibold text-navy">Kota/Kabupaten<input wire:model="city" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" placeholder="Bandung" /></label>
                <label class="block text-sm font-semibold text-navy sm:col-span-2">Alamat<input wire:model="address" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                <label class="block text-sm font-semibold text-navy">Telepon<input wire:model="phone" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                <label class="block text-sm font-semibold text-navy">Email sekolah<input wire:model="email" type="email" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                @error('email') <p class="text-sm text-red-600 sm:col-span-2">{{ $message }}</p> @enderror
                <label class="block text-sm font-semibold text-navy">Nama kepala sekolah<input wire:model="principalName" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                <label class="block text-sm font-semibold text-navy">NIP kepala sekolah<input wire:model="principalNip" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" placeholder="Kosongkan bila tidak ada" /></label>
            </div>
        </x-theme.card>

        <div class="space-y-6">
            <x-theme.card>
                <h2 class="text-lg">Logo</h2>
                <p class="mt-1 text-sm text-ink-soft">PNG atau JPG, maksimal 1 MB. Sebaiknya persegi dengan latar transparan.</p>
                <div class="mt-5 flex items-center gap-4">
                    <span class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-line bg-offwhite">
                        @if ($logo && $logo->isPreviewable())
                            <img src="{{ $logo->temporaryUrl() }}" alt="Pratinjau logo" class="size-full object-contain p-1.5">
                        @elseif ($profile->logoUrl())
                            <img src="{{ $profile->logoUrl() }}" alt="Logo {{ $profile->name }}" class="size-full object-contain p-1.5">
                        @else
                            <flux:icon.photo class="size-8 text-ink-soft" />
                        @endif
                    </span>
                    <div class="min-w-0 space-y-2">
                        <input wire:model="logo" type="file" accept="image/png,image/jpeg" class="block w-full text-sm" />
                        @if ($profile->logo_path && ! $logo)
                            <button type="button" wire:click="removeLogo" wire:confirm="Hapus logo sekolah?" class="text-sm font-semibold text-red-600">Hapus logo</button>
                        @endif
                    </div>
                </div>
                @error('logo') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
            </x-theme.card>

            <x-theme.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save,logo">Simpan profil sekolah</x-theme.button>
        </div>
    </form>
</div>
