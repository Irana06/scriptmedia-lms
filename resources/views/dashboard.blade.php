<x-layouts.guru-admin :title="__('Dashboard Guru & Admin')">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <x-theme.section-header
            eyebrow="Dashboard Guru"
            title="Selamat pagi, {{ auth()->user()?->name ?? 'Bapak/Ibu Guru' }}!"
            description="Ringkasan kegiatan belajar hari ini dan pekerjaan yang perlu ditindaklanjuti."
        />

        <x-theme.button href="#" variant="orange" class="shrink-0">
            <flux:icon.plus class="size-4" />
            Buat materi
        </x-theme.button>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan sekolah">
        <x-theme.card class="relative overflow-hidden">
            <div class="absolute -right-5 -top-5 size-20 rounded-full bg-tosca-tint"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-soft">Kelas aktif</p>
                    <p class="mt-2 text-3xl font-semibold text-navy">6</p>
                    <p class="mt-1 text-xs text-tosca">192 siswa terdaftar</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-2xl bg-tosca-tint text-tosca">
                    <flux:icon.users class="size-5" />
                </span>
            </div>
        </x-theme.card>

        <x-theme.card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-soft">Mengajar hari ini</p>
                    <p class="mt-2 text-3xl font-semibold text-navy">4</p>
                    <p class="mt-1 text-xs text-ink-soft">Jam berikutnya 09.30</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-2xl bg-orange/15 text-orange">
                    <flux:icon.calendar-days class="size-5" />
                </span>
            </div>
        </x-theme.card>

        <x-theme.card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-soft">Perlu dinilai</p>
                    <p class="mt-2 text-3xl font-semibold text-navy">28</p>
                    <p class="mt-1 text-xs text-orange">8 melewati target</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-2xl bg-orange/15 text-orange">
                    <flux:icon.clipboard-document-check class="size-5" />
                </span>
            </div>
        </x-theme.card>

        <x-theme.card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-soft">Kehadiran hari ini</p>
                    <p class="mt-2 text-3xl font-semibold text-navy">96%</p>
                    <p class="mt-1 text-xs text-tosca">Naik 2% dari kemarin</p>
                </div>
                <span class="flex size-11 items-center justify-center rounded-2xl bg-tosca-tint text-tosca">
                    <flux:icon.chart-bar class="size-5" />
                </span>
            </div>
        </x-theme.card>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-5 sm:px-6">
                <div>
                    <h2 class="text-lg">Jadwal mengajar hari ini</h2>
                    <p class="mt-1 text-sm text-ink-soft">Kamis, 6 Agustus 2026</p>
                </div>
                <x-theme.badge tone="tosca-soft">4 sesi</x-theme.badge>
            </div>

            <div class="divide-y divide-line">
                <article class="grid gap-3 px-5 py-4 sm:grid-cols-[6rem_1fr_auto] sm:items-center sm:px-6">
                    <div>
                        <p class="font-semibold text-navy">07.00</p>
                        <p class="text-xs text-ink-soft">45 menit</p>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm">Matematika · X IPA 1</h3>
                            <x-theme.badge tone="neutral">Selesai</x-theme.badge>
                        </div>
                        <p class="mt-1 text-xs text-ink-soft">Persamaan kuadrat · Ruang 12</p>
                    </div>
                    <x-theme.button href="#" variant="outline" class="min-h-9 px-3 py-2">Lihat kelas</x-theme.button>
                </article>

                <article class="grid gap-3 bg-tosca-tint/35 px-5 py-4 sm:grid-cols-[6rem_1fr_auto] sm:items-center sm:px-6">
                    <div>
                        <p class="font-semibold text-navy">09.30</p>
                        <p class="text-xs text-ink-soft">45 menit</p>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm">Matematika · XI IPA 2</h3>
                            <x-theme.badge tone="orange">Berikutnya</x-theme.badge>
                        </div>
                        <p class="mt-1 text-xs text-ink-soft">Turunan fungsi · Ruang 8</p>
                    </div>
                    <x-theme.button href="#" variant="navy" class="min-h-9 px-3 py-2">Buka kelas</x-theme.button>
                </article>

                <article class="grid gap-3 px-5 py-4 sm:grid-cols-[6rem_1fr_auto] sm:items-center sm:px-6">
                    <div>
                        <p class="font-semibold text-navy">11.15</p>
                        <p class="text-xs text-ink-soft">45 menit</p>
                    </div>
                    <div>
                        <h3 class="text-sm">Matematika · X IPA 3</h3>
                        <p class="mt-1 text-xs text-ink-soft">Fungsi dan grafik · Ruang 10</p>
                    </div>
                    <x-theme.button href="#" variant="outline" class="min-h-9 px-3 py-2">Detail</x-theme.button>
                </article>
            </div>
        </x-theme.card>

        <div class="space-y-6">
            <x-theme.card>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg">Perlu perhatian</h2>
                    <x-theme.badge tone="orange">8 baru</x-theme.badge>
                </div>
                <div class="mt-5 space-y-4">
                    <a href="#" class="group flex gap-3">
                        <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-orange/15 text-orange">
                            <flux:icon.clipboard-document-check class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-navy group-hover:text-tosca">8 tugas belum dinilai</span>
                            <span class="mt-0.5 block text-xs text-ink-soft">Latihan turunan · XI IPA 2</span>
                        </span>
                        <flux:icon.chevron-right class="mt-2 size-4 text-ink-soft" />
                    </a>
                    <a href="#" class="group flex gap-3">
                        <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca">
                            <flux:icon.user-minus class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-navy group-hover:text-tosca">3 siswa belum presensi</span>
                            <span class="mt-0.5 block text-xs text-ink-soft">Kelas X IPA 1 · Sesi pertama</span>
                        </span>
                        <flux:icon.chevron-right class="mt-2 size-4 text-ink-soft" />
                    </a>
                </div>
            </x-theme.card>

            <x-theme.card class="border-0 bg-gradient-to-br from-navy to-navy-mid text-white">
                <x-theme.badge tone="orange">Tip hari ini</x-theme.badge>
                <h2 class="mt-4 text-lg text-white">Materi lebih mudah ditemukan</h2>
                <p class="mt-2 text-sm leading-6 text-white/70">Kelompokkan materi berdasarkan bab agar siswa dapat mengulang pelajaran setelah kelas.</p>
                <a href="#" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-light">
                    Kelola materi
                    <flux:icon.arrow-right class="size-4" />
                </a>
            </x-theme.card>
        </div>
    </div>
</x-layouts.guru-admin>
