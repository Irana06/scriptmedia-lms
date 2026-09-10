<div class="mx-auto max-w-7xl space-y-6">
    <x-theme.section-header
        eyebrow="Data master"
        title="Import akun siswa & guru"
        description="Unggah data massal, periksa baris yang gagal, lalu unduh kartu akun untuk dibagikan secara manual."
    />

    @if ($latestImport)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            class="flex flex-col gap-4 rounded-card border border-tosca/25 bg-tosca-tint p-5 sm:flex-row sm:items-center"
        >
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-tosca text-navy">
                <flux:icon.check-circle class="size-6" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-navy">Import selesai diproses</p>
                <p class="mt-1 text-sm text-ink-soft">
                    {{ $latestImport->success_count }} berhasil dan {{ $latestImport->failed_count }} gagal dari {{ $latestImport->total_rows }} baris.
                </p>
            </div>
            @if ($latestImport->result_file_path && ! $latestImport->downloaded_at)
                <x-theme.button href="{{ route('admin.imports.credentials', $latestImport) }}" variant="orange">
                    <flux:icon.arrow-down-tray class="size-5" />
                    Unduh kartu akun
                </x-theme.button>
            @endif
            <button type="button" class="absolute right-4 top-4 text-ink-soft sm:static" aria-label="Tutup pemberitahuan" @click="show = false">
                <flux:icon.x-mark class="size-5" />
            </button>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
        <x-theme.card>
            <div class="flex items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-navy text-white">
                    <flux:icon.document-arrow-up class="size-6" />
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-navy">Unggah data</h3>
                    <p class="mt-1 text-sm leading-6 text-ink-soft">Gunakan template agar nama kolom dan format data terbaca dengan benar.</p>
                </div>
            </div>

            <form wire:submit="import" class="mt-6 space-y-6">
                <fieldset>
                    <legend class="text-sm font-semibold text-navy">Jenis akun</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label @class([
                            'cursor-pointer rounded-2xl border p-4 transition',
                            'border-tosca bg-tosca-tint' => $type === 'siswa',
                            'border-line bg-white hover:border-tosca/50' => $type !== 'siswa',
                        ])>
                            <input wire:model.live="type" type="radio" value="siswa" class="sr-only" />
                            <span class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-white text-tosca shadow-sm"><flux:icon.user-group class="size-5" /></span>
                                <span>
                                    <span class="block text-sm font-semibold text-navy">Siswa</span>
                                    <span class="mt-0.5 block text-xs text-ink-soft">NISN, NIK, gender, kelas</span>
                                </span>
                            </span>
                        </label>
                        <label @class([
                            'cursor-pointer rounded-2xl border p-4 transition',
                            'border-tosca bg-tosca-tint' => $type === 'guru',
                            'border-line bg-white hover:border-tosca/50' => $type !== 'guru',
                        ])>
                            <input wire:model.live="type" type="radio" value="guru" class="sr-only" />
                            <span class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-white text-tosca shadow-sm"><flux:icon.academic-cap class="size-5" /></span>
                                <span>
                                    <span class="block text-sm font-semibold text-navy">Guru</span>
                                    <span class="mt-0.5 block text-xs text-ink-soft">Nama, email, dan NIP</span>
                                </span>
                            </span>
                        </label>
                    </div>
                    @error('type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <div class="flex items-center justify-between gap-4">
                        <label for="import-file" class="text-sm font-semibold text-navy">File Excel atau CSV</label>
                        <a href="{{ route('admin.imports.template', $type) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tosca hover:text-navy">
                            <flux:icon.arrow-down-tray class="size-4" />
                            Unduh template {{ ucfirst($type) }}
                        </a>
                    </div>
                    <label for="import-file" class="mt-3 flex min-h-40 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-line bg-offwhite/60 px-6 py-8 text-center transition hover:border-tosca hover:bg-tosca-tint/40">
                        <flux:icon.cloud-arrow-up class="size-9 text-tosca" />
                        @if ($file)
                            <span class="mt-3 text-sm font-semibold text-navy">{{ $file->getClientOriginalName() }}</span>
                            <span class="mt-1 text-xs text-ink-soft">Klik untuk mengganti file</span>
                        @else
                            <span class="mt-3 text-sm font-semibold text-navy">Pilih file dari perangkat</span>
                            <span class="mt-1 text-xs text-ink-soft">XLSX, XLS, atau CSV — maksimal 5 MB</span>
                        @endif
                        <input id="import-file" wire:model="file" type="file" accept=".xlsx,.xls,.csv" class="sr-only" />
                    </label>
                    <div wire:loading wire:target="file" class="mt-2 text-sm text-tosca">Menyiapkan file...</div>
                    @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-ink-soft">Akun lama diperbarui dan mendapat password awal baru.</p>
                    <x-theme.button type="submit" variant="navy" wire:loading.attr="disabled" wire:target="import,file">
                        <span wire:loading.remove wire:target="import">Proses import</span>
                        <span wire:loading wire:target="import">Memproses data...</span>
                        <flux:icon.arrow-right class="size-4" wire:loading.remove wire:target="import" />
                    </x-theme.button>
                </div>
            </form>
        </x-theme.card>

        <div class="space-y-6">
            <x-theme.card>
                <h3 class="font-semibold text-navy">Sebelum mengunggah</h3>
                <ol class="mt-4 space-y-4 text-sm text-ink-soft">
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-semibold text-white">1</span><span>Unduh dan isi template sesuai jenis akun.</span></li>
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-semibold text-white">2</span><span>Pastikan kelas siswa sudah tersedia pada tahun ajaran aktif.</span></li>
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-semibold text-white">3</span><span>Unduh kartu akun setelah proses selesai. File hanya tersedia satu kali.</span></li>
                </ol>

                <div class="mt-5 border-t border-line pt-4">
                    <p class="text-sm font-semibold text-navy">Kolom yang boleh kosong</p>
                    <ul class="mt-2 space-y-1.5 text-xs leading-5 text-ink-soft">
                        <li><strong class="text-navy">Siswa</strong> — isi NISN <em>atau</em> NIS, tidak harus keduanya. Siswa baru yang NISN-nya belum terbit tetap bisa dibuatkan akun memakai nomor induk sekolah. NIK boleh dikosongkan.</li>
                        <li><strong class="text-navy">Guru</strong> — NIP dan NUPTK keduanya opsional. Guru yayasan dan honorer umumnya tidak memiliki NIP.</li>
                    </ul>
                    <p class="mt-3 text-xs leading-5 text-ink-soft">Berkas hasil ekspor Dapodik atau EMIS bisa langsung diunggah tanpa mengganti nama kolomnya.</p>
                </div>
            </x-theme.card>

            <div class="rounded-card border border-orange/35 bg-orange/10 p-5">
                <div class="flex gap-3">
                    <flux:icon.shield-check class="size-6 shrink-0 text-navy" />
                    <div>
                        <p class="text-sm font-semibold text-navy">Kata sandi tetap privat</p>
                        <p class="mt-1 text-xs leading-5 text-ink-soft">Password awal tidak disimpan sebagai teks di database. Simpan hasil unduhan di tempat aman dan bagikan langsung kepada pemilik akun.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-theme.card :padding="false" class="overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="font-semibold text-navy">Riwayat import</h3>
                <p class="mt-1 text-sm text-ink-soft">15 proses terbaru beserta ringkasan barisnya.</p>
            </div>
        </div>

        @if ($imports->isEmpty())
            <div class="px-6 py-12 text-center">
                <flux:icon.inbox class="mx-auto size-9 text-ink-soft/50" />
                <p class="mt-3 text-sm font-semibold text-navy">Belum ada riwayat import</p>
                <p class="mt-1 text-xs text-ink-soft">Proses yang selesai akan muncul di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-left text-sm">
                    <thead class="bg-offwhite/80 text-xs uppercase tracking-wide text-ink-soft">
                        <tr>
                            <th class="px-5 py-3 font-semibold sm:px-6">Waktu</th>
                            <th class="px-5 py-3 font-semibold">Jenis</th>
                            <th class="px-5 py-3 font-semibold">Hasil</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold sm:px-6">Kartu akun</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line bg-white">
                        @foreach ($imports as $import)
                            <tr wire:key="import-{{ $import->id }}" class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <p class="font-medium text-navy">{{ $import->created_at?->format('d M Y') }}</p>
                                    <p class="mt-0.5 text-xs text-ink-soft">{{ $import->created_at?->format('H:i') }} · {{ $import->importer->name }}</p>
                                </td>
                                <td class="px-5 py-4"><x-theme.badge tone="tosca-soft">{{ ucfirst($import->type) }}</x-theme.badge></td>
                                <td class="px-5 py-4">
                                    <p><span class="font-semibold text-tosca">{{ $import->success_count }}</span> berhasil · <span class="font-semibold text-red-600">{{ $import->failed_count }}</span> gagal</p>
                                    @if ($import->failures)
                                        <details class="mt-2 max-w-sm">
                                            <summary class="cursor-pointer text-xs font-semibold text-navy">Lihat kegagalan</summary>
                                            <ul class="mt-2 space-y-1 text-xs leading-5 text-ink-soft">
                                                @foreach ($import->failures as $failure)
                                                    <li>Baris {{ $failure['row'] }}: {{ $failure['message'] }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-theme.badge :tone="$import->status === 'done' ? 'tosca' : ($import->status === 'failed' ? 'orange' : 'neutral')">
                                        {{ $import->status === 'done' ? 'Selesai' : ($import->status === 'failed' ? 'Gagal' : 'Diproses') }}
                                    </x-theme.badge>
                                </td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    @if ($import->result_file_path && ! $import->downloaded_at)
                                        <a href="{{ route('admin.imports.credentials', $import) }}" class="inline-flex items-center gap-1.5 font-semibold text-tosca hover:text-navy">
                                            <flux:icon.arrow-down-tray class="size-4" /> Unduh sekali
                                        </a>
                                    @elseif ($import->downloaded_at)
                                        <span class="text-xs text-ink-soft">Sudah diunduh</span>
                                    @else
                                        <span class="text-xs text-ink-soft">Tidak tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-theme.card>
</div>
