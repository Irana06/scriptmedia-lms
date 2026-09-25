<div class="mx-auto max-w-5xl space-y-6">
    <x-theme.section-header eyebrow="Administrasi akademik" title="Generate rapor siswa" description="Pilih semester dan siswa untuk mengunduh rekap nilai serta kehadiran dalam PDF." />
    <x-theme.card>
        <div class="grid gap-5 md:grid-cols-3">
            <label class="text-sm font-semibold text-navy">Semester<select wire:model.live="semesterId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@foreach($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->academicYear->year_label }} · {{ $semester->name }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold text-navy">Kelas<select wire:model.live="classId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@forelse($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@empty<option value="">Belum ada kelas</option>@endforelse</select></label>
            <label class="text-sm font-semibold text-navy">Siswa<select wire:model.live="studentId" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3">@forelse($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->nisn }}</option>@empty<option value="">Belum ada siswa</option>@endforelse</select></label>
        </div>
        <div class="mt-6 flex flex-col gap-3 rounded-2xl bg-offwhite p-5 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-navy">Rapor semester</p><p class="mt-1 text-sm text-ink-soft">PDF memuat identitas siswa, seluruh nilai mapel, predikat, dan rekap presensi.</p></div>@if($semesterId && $studentId)<x-theme.button href="{{ route('admin.report-cards.download', ['semester' => $semesterId, 'student' => $studentId]) }}" variant="orange"><flux:icon.arrow-down-tray class="size-5" />Unduh PDF</x-theme.button>@endif</div>
        @if ($semesterId && $classes->contains('id', (int) $classId))
            <div class="mt-3 flex flex-col gap-3 rounded-2xl border border-line p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-semibold text-navy">Rekap satu kelas (Excel)</p><p class="mt-1 text-sm text-ink-soft">Leger nilai semua mapel dan rekap presensi — siap dipindahkan ke e-Rapor atau Dapodik.</p></div>
                <div class="flex flex-wrap gap-2">
                    <x-theme.button variant="outline" href="{{ route('admin.report-cards.ledger', ['semester' => $semesterId, 'schoolClass' => $classId]) }}"><flux:icon.table-cells class="size-4" />Leger nilai</x-theme.button>
                    <x-theme.button variant="outline" href="{{ route('admin.report-cards.attendance', ['semester' => $semesterId, 'schoolClass' => $classId]) }}"><flux:icon.calendar-days class="size-4" />Rekap presensi</x-theme.button>
                </div>
            </div>
        @endif
    </x-theme.card>
</div>
