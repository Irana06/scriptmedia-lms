<div class="mx-auto max-w-7xl space-y-6">
    <x-theme.section-header eyebrow="Evaluasi kelas" title="Nilai akhir & presensi" description="Rekap hasil belajar dan catat kehadiran seluruh kelas dalam satu kali simpan." />

    @if (session('evaluation_status'))
        <div class="rounded-2xl border border-tosca/25 bg-tosca-tint px-4 py-3 text-sm font-semibold text-navy">{{ session('evaluation_status') }}</div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
        <div class="flex min-w-max gap-1">
            <button wire:click="$set('tab', 'grades')" @class(['rounded-xl px-5 py-2.5 text-sm font-semibold', 'bg-navy text-white' => $tab === 'grades', 'text-ink-soft' => $tab !== 'grades'])>Nilai akhir</button>
            <button wire:click="$set('tab', 'attendance')" @class(['rounded-xl px-5 py-2.5 text-sm font-semibold', 'bg-navy text-white' => $tab === 'attendance', 'text-ink-soft' => $tab !== 'attendance'])>Presensi harian</button>
        </div>
    </div>

    @if ($tab === 'grades')
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="grid gap-4 border-b border-line p-5 sm:grid-cols-2 lg:grid-cols-[1fr_240px_auto] lg:items-end sm:p-6">
                <label class="text-sm font-semibold text-navy">Kelas & mata pelajaran<select wire:model.live="classSubjectId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@foreach($teachingAssignments as $item)<option value="{{ $item->id }}">{{ $item->schoolClass->name }} · {{ $item->subject->name }}</option>@endforeach</select></label>
                <label class="text-sm font-semibold text-navy">Semester<select wire:model.live="semesterId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</select></label>
                <x-theme.button type="button" wire:click="autoFillScores" wire:loading.attr="disabled" wire:target="autoFillScores" variant="outline"><flux:icon.calculator class="size-4" /><span wire:loading.remove wire:target="autoFillScores">Hitung otomatis</span><span wire:loading wire:target="autoFillScores">Menghitung...</span></x-theme.button>
            </div>
            @if ($selectedAssignment && $selectedAssignment->schoolClass->students->isNotEmpty())
                <form wire:submit="saveGrades">
                    <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-offwhite text-ink-soft"><tr><th class="px-5 py-3 sm:px-6">Siswa</th><th class="px-5 py-3">NISN</th><th class="px-5 py-3">Nilai akhir</th><th class="px-5 py-3">Predikat</th></tr></thead><tbody class="divide-y divide-line">
                        @foreach($selectedAssignment->schoolClass->students->sortBy('name') as $student)
                            @php($score = $scores[$student->id] ?? '')
                            <tr><td class="px-5 py-4 font-semibold text-navy sm:px-6">{{ $student->name }}</td><td class="px-5 py-4 text-ink-soft">{{ $student->nisn }}</td><td class="px-5 py-3"><input wire:model="scores.{{ $student->id }}" type="number" min="0" max="100" step="0.01" class="min-h-10 w-28 rounded-xl border border-line px-3"></td><td class="px-5 py-4"><x-theme.badge tone="neutral">{{ $score === '' ? '—' : ((float)$score >= 90 ? 'A' : ((float)$score >= 80 ? 'B' : ((float)$score >= 70 ? 'C' : ((float)$score >= 60 ? 'D' : 'E')))) }}</x-theme.badge></td></tr>
                        @endforeach
                    </tbody></table></div>
                    @error('scores.*') <p class="px-6 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="flex justify-end border-t border-line p-5 sm:px-6"><x-theme.button type="submit" wire:loading.attr="disabled" wire:target="saveGrades"><span wire:loading.remove wire:target="saveGrades">Simpan nilai kelas</span><span wire:loading wire:target="saveGrades">Menyimpan...</span></x-theme.button></div>
                </form>
            @else
                <div class="p-12 text-center text-sm text-ink-soft">Belum ada siswa atau mata pelajaran yang dapat dinilai.</div>
            @endif
        </x-theme.card>
    @else
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="grid gap-4 border-b border-line p-5 sm:grid-cols-2 sm:p-6">
                <label class="text-sm font-semibold text-navy">Kelas<select wire:model.live="attendanceClassId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@foreach($attendanceClasses as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></label>
                <label class="text-sm font-semibold text-navy">Tanggal<input wire:model.live="attendanceDate" type="date" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3"></label>
            </div>
            @if ($selectedClass && $selectedClass->students->isNotEmpty())
                <form wire:submit="saveAttendance">
                    <div class="divide-y divide-line">
                        @foreach($selectedClass->students->sortBy('name') as $student)
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6"><div class="min-w-0 flex-1"><p class="font-semibold text-navy">{{ $student->name }}</p><p class="mt-0.5 text-sm text-ink-soft">{{ $student->nisn }}</p></div><div class="grid grid-cols-4 gap-2">@foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $value => $label)<label class="cursor-pointer"><input wire:model="statuses.{{ $student->id }}" type="radio" value="{{ $value }}" class="peer sr-only"><span class="block rounded-xl border border-line px-3 py-2 text-center text-sm font-semibold text-ink-soft peer-checked:border-tosca peer-checked:bg-tosca-tint peer-checked:text-navy">{{ $label }}</span></label>@endforeach</div></div>
                        @endforeach
                    </div>
                    @error('statuses.*') <p class="px-6 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="flex justify-end border-t border-line p-5 sm:px-6"><x-theme.button type="submit" wire:loading.attr="disabled" wire:target="saveAttendance"><flux:icon.check class="size-4" /><span wire:loading.remove wire:target="saveAttendance">Simpan seluruh presensi</span><span wire:loading wire:target="saveAttendance">Menyimpan...</span></x-theme.button></div>
                </form>
            @else
                <div class="p-12 text-center text-sm text-ink-soft">Belum ada siswa di kelas ini.</div>
            @endif
        </x-theme.card>
    @endif
</div>
