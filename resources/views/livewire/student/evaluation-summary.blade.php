<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <x-theme.section-header eyebrow="Hasil belajarmu" title="Nilai & presensi" description="Rekap hasil akhir dan kehadiran setiap semester." />
        <label class="min-w-64 text-sm font-semibold text-navy">Semester
            <select wire:model.live="semesterId" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3">
                @foreach($semesters as $item)
                    <option value="{{ $item->id }}">{{ $item->academicYear->year_label }} · {{ $item->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-[1.2fr_2fr]">
        <x-theme.card class="border-tosca/30 bg-tosca-tint/40">
            <p class="text-sm text-ink-soft">Tingkat kehadiran</p>
            <div class="mt-2 flex items-end gap-2">
                <p class="text-4xl font-semibold text-navy">{{ $attendanceRate }}%</p>
                <span class="pb-1 text-xs text-ink-soft">semester ini</span>
            </div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-tosca" style="width: {{ $attendanceRate }}%"></div></div>
        </x-theme.card>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach(['hadir' => ['Hadir','tosca'], 'izin' => ['Izin','orange'], 'sakit' => ['Sakit','navy'], 'alpa' => ['Alpa','neutral']] as $status => [$label,$tone])
                <x-theme.card class="text-center">
                    <p class="text-3xl font-semibold text-navy">{{ $attendanceCounts[$status] }}</p>
                    <x-theme.badge :tone="$tone" class="mt-2">{{ $label }}</x-theme.badge>
                </x-theme.card>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.25fr_.75fr]">
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="border-b border-line px-5 py-5 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-xl">Nilai akhir mata pelajaran</h2><p class="mt-1 text-sm text-ink-soft">{{ $semester ? $semester->academicYear->year_label.' · Semester '.$semester->name : 'Belum ada semester' }}</p></div><x-theme.badge tone="neutral">Klik baris untuk rincian</x-theme.badge></div>
                <p class="mt-3 rounded-xl bg-offwhite px-3 py-2 text-xs leading-5 text-ink-soft"><strong class="text-navy">Apa nilai ini?</strong> Ini nilai akhir yang diterbitkan guru. Hitung otomatis memakai rata-rata nilai tugas dan kuis pada semester terpilih, lalu guru dapat menyesuaikannya sebelum disimpan.</p>
            </div>
            @if($grades->isEmpty())
                <div class="p-12 text-center text-sm text-ink-soft">Nilai akhir belum diterbitkan.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-offwhite text-ink-soft"><tr><th class="px-5 py-3 sm:px-6">Mata pelajaran</th><th class="px-5 py-3">Guru</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Predikat</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach($grades as $grade)
                                <tr @class(['transition hover:bg-tosca-tint/40', 'bg-tosca-tint/50' => (int) $classSubjectId === $grade->class_subject_id])><td class="p-0 sm:pl-1"><a class="block px-5 py-4 font-semibold text-navy sm:px-5" href="{{ route('student.evaluation.index', ['semesterId' => $semesterId, 'classSubjectId' => $grade->class_subject_id]) }}" wire:navigate>{{ $grade->classSubject->subject->name }}<span class="mt-1 block text-xs font-normal text-tosca-ink">Lihat rincian nilai →</span></a></td><td class="px-5 py-4 text-ink-soft">{{ $grade->classSubject->teacher->name }}</td><td class="px-5 py-4 text-lg font-semibold text-navy">{{ number_format((float)$grade->final_score, 2) }}</td><td class="px-5 py-4"><x-theme.badge tone="tosca">{{ $grade->predikat }}</x-theme.badge></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-theme.card>

        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Riwayat presensi</h2><p class="mt-1 text-sm text-ink-soft">Maksimal 30 catatan terbaru</p></div>
            <div class="divide-y divide-line">
                @forelse($attendanceRecords as $record)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                        <div><p class="text-sm font-semibold text-navy">{{ $record->date->translatedFormat('l, d M Y') }}</p><p class="mt-0.5 text-xs text-ink-soft">Catatan kehadiran kelas</p></div>
                        <x-theme.badge :tone="match($record->status) {'hadir' => 'tosca', 'izin' => 'orange', 'sakit' => 'navy', default => 'danger'}">{{ ucfirst($record->status) }}</x-theme.badge>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-ink-soft">Belum ada catatan presensi.</div>
                @endforelse
            </div>
        </x-theme.card>
    </div>

    @if($selectedGrade)
        <x-theme.card id="rincian-nilai" :padding="false" class="overflow-hidden border-tosca/30">
            <div class="flex flex-col justify-between gap-4 border-b border-line bg-tosca-tint/35 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-tosca-ink">Rincian nilai</p><h2 class="mt-1 text-xl">{{ $selectedGrade->classSubject->subject->name }}</h2><p class="mt-1 text-sm text-ink-soft">Nilai akhir {{ number_format((float) $selectedGrade->final_score, 2) }} · Predikat {{ $selectedGrade->predikat }}</p></div>
                <a href="{{ route('student.evaluation.index', ['semesterId' => $semesterId]) }}" class="text-sm font-semibold text-navy" wire:navigate>Tutup rincian ×</a>
            </div>
            <div class="grid gap-0 divide-y divide-line lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                <div class="p-5 sm:p-6"><div class="flex items-center justify-between"><h3 class="font-semibold text-navy">Nilai tugas</h3><x-theme.badge tone="orange">{{ $assignmentScores->count() }} dinilai</x-theme.badge></div><div class="mt-4 space-y-3">@forelse($assignmentScores as $item)<div class="flex items-center justify-between gap-4 rounded-xl bg-offwhite p-3"><div><p class="text-sm font-semibold text-navy">{{ $item->submission->assignment->title }}</p><p class="mt-0.5 text-xs text-ink-soft">Dikumpulkan {{ $item->submission->submitted_at->translatedFormat('d M Y') }}</p>@if($item->feedback)<p class="mt-1 text-xs text-ink-soft">{{ $item->feedback }}</p>@endif</div><strong class="text-lg text-tosca-ink">{{ number_format((float) $item->score, 0) }}</strong></div>@empty<p class="rounded-xl bg-offwhite p-4 text-sm text-ink-soft">Belum ada tugas yang dinilai pada semester ini.</p>@endforelse</div></div>
                <div class="p-5 sm:p-6"><div class="flex items-center justify-between"><h3 class="font-semibold text-navy">Nilai kuis</h3><x-theme.badge tone="navy">{{ $quizScores->whereNotNull('score')->count() }} dinilai</x-theme.badge></div><div class="mt-4 space-y-3">@forelse($quizScores as $item)<div class="flex items-center justify-between gap-4 rounded-xl bg-offwhite p-3"><div><p class="text-sm font-semibold text-navy">{{ $item->quiz->title }}</p><p class="mt-0.5 text-xs text-ink-soft">Selesai {{ $item->submitted_at?->translatedFormat('d M Y, H:i') }}</p></div><strong class="text-lg text-navy">{{ $item->score === null ? 'Diproses' : number_format((float) $item->score, 0) }}</strong></div>@empty<p class="rounded-xl bg-offwhite p-4 text-sm text-ink-soft">Belum ada kuis yang selesai pada semester ini.</p>@endforelse</div></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4 text-sm sm:px-6"><p class="text-ink-soft">Rata-rata sumber otomatis: <strong class="text-navy">{{ $sourceAverage === null ? 'Belum tersedia' : number_format($sourceAverage, 2) }}</strong></p>@if($sourceAverage !== null && abs($sourceAverage - (float) $selectedGrade->final_score) >= 0.01)<x-theme.badge tone="orange">Disesuaikan guru</x-theme.badge>@else<x-theme.badge tone="tosca">Sesuai hitung otomatis</x-theme.badge>@endif</div>
        </x-theme.card>
    @endif
</div>
