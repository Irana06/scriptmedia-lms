<x-layouts.siswa :title="__('Dashboard Siswa')">
    <section class="relative overflow-hidden rounded-card bg-gradient-to-br from-navy to-navy-mid px-5 py-7 text-white shadow-[0_18px_40px_-24px_rgba(11,37,69,0.65)] sm:px-8 sm:py-9">
        <div class="absolute -right-12 -top-16 size-48 rounded-full border-[28px] border-white/5"></div>
        <div class="absolute -bottom-20 right-24 size-36 rounded-full bg-tosca/15 blur-sm"></div>
        <div class="relative max-w-2xl">
            <x-theme.badge tone="orange">Kamis, 6 Agustus</x-theme.badge>
            <h1 class="mt-4 text-2xl leading-tight text-white sm:text-3xl">Halo, {{ auth()->user()?->name ?? 'Siswa' }}! Siap belajar hari ini?</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-white/70 sm:text-base">Ada 4 pelajaran dan 2 tugas yang perlu kamu perhatikan. Yuk, atur waktumu supaya tetap santai.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-theme.button href="#" variant="orange">
                    Lihat jadwal
                    <flux:icon.arrow-right class="size-4" />
                </x-theme.button>
                <x-theme.button href="#" class="border border-white/20 bg-white/10 text-white hover:bg-white/15 focus-visible:ring-white">
                    2 tugas aktif
                </x-theme.button>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <div class="flex items-end justify-between gap-4">
            <x-theme.section-header eyebrow="Hari ini" title="Jadwal pelajaran" description="Pelajaran yang akan kamu ikuti di sekolah." />
            <a href="#" class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-tosca sm:flex">
                Semua jadwal
                <flux:icon.chevron-right class="size-4" />
            </a>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-theme.card class="border-t-4 border-t-tosca">
                <div class="flex items-center justify-between gap-2">
                    <x-theme.badge tone="neutral">07.00–07.45</x-theme.badge>
                    <span class="text-xs font-semibold text-tosca">Selesai</span>
                </div>
                <h3 class="mt-5 text-lg">Bahasa Indonesia</h3>
                <p class="mt-1 text-sm text-ink-soft">Ruang 12 · Ibu Maya</p>
            </x-theme.card>

            <x-theme.card class="border-t-4 border-t-orange bg-orange/5">
                <div class="flex items-center justify-between gap-2">
                    <x-theme.badge tone="orange">09.30–10.15</x-theme.badge>
                    <span class="text-xs font-semibold text-orange">Berikutnya</span>
                </div>
                <h3 class="mt-5 text-lg">Matematika</h3>
                <p class="mt-1 text-sm text-ink-soft">Ruang 8 · Pak Andi</p>
            </x-theme.card>

            <x-theme.card class="border-t-4 border-t-navy-mid">
                <div class="flex items-center justify-between gap-2">
                    <x-theme.badge tone="neutral">10.30–11.15</x-theme.badge>
                </div>
                <h3 class="mt-5 text-lg">Biologi</h3>
                <p class="mt-1 text-sm text-ink-soft">Lab IPA · Ibu Rani</p>
            </x-theme.card>

            <x-theme.card class="border-t-4 border-t-tosca">
                <div class="flex items-center justify-between gap-2">
                    <x-theme.badge tone="neutral">12.30–13.15</x-theme.badge>
                </div>
                <h3 class="mt-5 text-lg">Bahasa Inggris</h3>
                <p class="mt-1 text-sm text-ink-soft">Ruang 12 · Mr. Daniel</p>
            </x-theme.card>
        </div>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-5 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tosca">Prioritas</p>
                    <h2 class="mt-1 text-xl">Tugas terdekat</h2>
                </div>
                <x-theme.badge tone="orange">2 aktif</x-theme.badge>
            </div>
            <div class="divide-y divide-line">
                <article class="flex gap-4 px-5 py-5 sm:px-6">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-orange/15 text-orange">
                        <flux:icon.calculator class="size-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="text-base">Latihan turunan fungsi</h3>
                                <p class="mt-1 text-sm text-ink-soft">Matematika · Pak Andi</p>
                            </div>
                            <x-theme.badge tone="orange">Besok, 20.00</x-theme.badge>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-offwhite">
                            <div class="h-full w-2/3 rounded-full bg-orange"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-soft">Sudah dikerjakan 6 dari 10 soal</p>
                    </div>
                </article>

                <article class="flex gap-4 px-5 py-5 sm:px-6">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-tosca-tint text-tosca">
                        <flux:icon.book-open class="size-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="text-base">Rangkuman sistem gerak</h3>
                                <p class="mt-1 text-sm text-ink-soft">Biologi · Ibu Rani</p>
                            </div>
                            <x-theme.badge tone="tosca-soft">Senin, 08.00</x-theme.badge>
                        </div>
                        <x-theme.button href="#" variant="outline" class="mt-4 min-h-9 px-3 py-2">Mulai mengerjakan</x-theme.button>
                    </div>
                </article>
            </div>
        </x-theme.card>

        <x-theme.card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tosca">Progres minggu ini</p>
                    <h2 class="mt-1 text-xl">Tetap konsisten!</h2>
                </div>
                <span class="flex size-12 items-center justify-center rounded-full bg-tosca-tint text-lg font-semibold text-tosca">82</span>
            </div>

            <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-offwhite px-2 py-4">
                    <p class="text-xl font-semibold text-navy">4/5</p>
                    <p class="mt-1 text-xs text-ink-soft">Hadir</p>
                </div>
                <div class="rounded-2xl bg-offwhite px-2 py-4">
                    <p class="text-xl font-semibold text-navy">3</p>
                    <p class="mt-1 text-xs text-ink-soft">Tugas selesai</p>
                </div>
                <div class="rounded-2xl bg-offwhite px-2 py-4">
                    <p class="text-xl font-semibold text-navy">86</p>
                    <p class="mt-1 text-xs text-ink-soft">Rata-rata</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-tosca-tint p-4">
                <div class="flex gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-tosca text-white">
                        <flux:icon.sparkles class="size-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-navy">Kerja bagus!</p>
                        <p class="mt-1 text-xs leading-5 text-ink-soft">Selesaikan satu tugas lagi untuk mencapai target mingguanmu.</p>
                    </div>
                </div>
            </div>
        </x-theme.card>
    </div>
</x-layouts.siswa>
