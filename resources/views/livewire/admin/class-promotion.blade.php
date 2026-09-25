<div>
    <x-theme.section-header
        eyebrow="Akhir tahun ajaran"
        title="Kenaikan kelas"
        description="Pindahkan seluruh siswa ke kelas tahun ajaran baru sekaligus. Kelas lama tetap tersimpan, jadi rapor tahun lalu masih bisa dibuka."
    />

    @if (session('promotion_status'))
        <div class="mt-6 rounded-card border border-tosca/40 bg-tosca-tint/60 px-5 py-4 text-sm text-navy" role="status">{{ session('promotion_status') }}</div>
    @endif

    @if ($summary)
        <div class="mt-6 rounded-card border border-tosca/40 bg-tosca-tint/60 p-5" role="status" data-test="promotion-summary">
            <p class="font-semibold text-navy">Kenaikan kelas selesai</p>
            <p class="mt-1 text-sm text-ink-soft">{{ $summary['moved'] }} siswa dipindahkan · {{ $summary['graduated'] }} siswa lulus · {{ $summary['skipped'] }} tidak diubah</p>
        </div>
    @endif

    <x-theme.card class="mt-6">
        <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="block text-sm font-semibold text-navy">Dari tahun ajaran
                <select wire:model.live="sourceYearId" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3 text-sm">
                    <option value="">Pilih</option>
                    @foreach ($years as $year)<option value="{{ $year->id }}">{{ $year->year_label }}{{ $year->is_active ? ' (aktif)' : '' }}</option>@endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-navy">Ke tahun ajaran
                <select wire:model.live="targetYearId" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3 text-sm">
                    <option value="">Pilih</option>
                    @foreach ($years as $year)<option value="{{ $year->id }}">{{ $year->year_label }}</option>@endforeach
                </select>
            </label>
            @if ($targetYear)
                <x-theme.button variant="outline" wire:click="copyClassesToTarget" wire:confirm="Buat kelas dengan nama yang sama di tahun ajaran {{ $targetYear->year_label }}?">
                    <flux:icon.document-duplicate class="size-4" />Salin daftar kelas
                </x-theme.button>
            @endif
        </div>
        @error('targetYearId') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @if ($years->count() < 2)
            <p class="mt-4 text-sm text-ink-soft">Buat dulu tahun ajaran berikutnya di <a href="{{ route('admin.academic.index', ['tab' => 'years']) }}" class="font-semibold text-tosca-ink" wire:navigate>Struktur Akademik</a>.</p>
        @elseif ($targetYear && $targetClasses->isEmpty())
            <p class="mt-4 text-sm text-ink-soft">Tahun ajaran {{ $targetYear->year_label }} belum punya kelas. Klik <strong>Salin daftar kelas</strong> untuk membuatnya dari tahun asal.</p>
        @endif
    </x-theme.card>

    @if ($sourceClasses->isNotEmpty() && $targetYear)
        <form wire:submit="promote" class="mt-6 space-y-4">
            @foreach ($sourceClasses as $class)
                <x-theme.card :padding="false" class="overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg">{{ $class->name }}</h2>
                            <p class="text-xs text-ink-soft">{{ $class->students->count() }} siswa</p>
                        </div>
                        <label class="flex items-center gap-3 text-sm font-semibold text-navy">
                            <flux:icon.arrow-right class="size-4 text-ink-soft" />
                            <select wire:model="classTargets.{{ $class->id }}" class="min-h-11 w-56 rounded-xl border border-line bg-white px-3 text-sm">
                                <option value="">Jangan dipindahkan</option>
                                @foreach ($targetClasses as $targetClass)<option value="{{ $targetClass->id }}">Naik ke {{ $targetClass->name }}</option>@endforeach
                                <option value="lulus">Lulus</option>
                            </select>
                        </label>
                    </div>
                    @if ($class->students->isNotEmpty())
                        <details class="group">
                            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-tosca-ink sm:px-6">Atur siswa tertentu (tinggal kelas, pindah, lulus)</summary>
                            <div class="divide-y divide-line border-t border-line">
                                @foreach ($class->students as $student)
                                    <div class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                        <span class="text-sm text-navy">{{ $student->name }} <span class="text-xs text-ink-soft">{{ $student->nisn ?: $student->nis }}</span></span>
                                        <select wire:model="studentOverrides.{{ $student->id }}" class="min-h-10 w-56 rounded-xl border border-line bg-white px-3 text-sm">
                                            <option value="">Ikut kelas</option>
                                            @foreach ($targetClasses as $targetClass)<option value="{{ $targetClass->id }}">{{ $targetClass->name }}</option>@endforeach
                                            <option value="lulus">Lulus</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </x-theme.card>
            @endforeach

            @error('classTargets') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <x-theme.card class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex items-center gap-3 text-sm text-navy">
                    <input type="checkbox" wire:model="activateTarget" class="size-4 rounded border-line text-tosca">
                    Jadikan {{ $targetYear->year_label }} tahun ajaran aktif setelah selesai
                </label>
                <x-theme.button type="submit" variant="orange" wire:confirm="Proses kenaikan kelas sekarang? Penempatan siswa di tahun ajaran tujuan akan diganti sesuai pilihan di atas." wire:loading.attr="disabled" wire:target="promote">
                    <flux:icon.arrow-trending-up class="size-4" />Proses kenaikan kelas
                </x-theme.button>
            </x-theme.card>
        </form>
    @endif
</div>
