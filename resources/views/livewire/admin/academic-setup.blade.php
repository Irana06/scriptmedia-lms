<div>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <x-theme.section-header
            eyebrow="Pengaturan Akademik"
            title="Struktur tahun ajaran"
            description="Siapkan semester, kelas, pengampu, siswa, dan jadwal dari satu ruang kerja."
        />
        <div class="flex items-center gap-2 text-xs text-ink-soft">
            <span class="size-2 rounded-full bg-tosca"></span>
            Tersimpan langsung ke data sekolah
        </div>
    </div>

    @if (session('academic_status'))
        <div class="mt-5 flex items-center gap-3 rounded-card border border-tosca/25 bg-tosca-tint px-4 py-3 text-sm text-navy" role="status">
            <flux:icon.check-circle class="size-5 shrink-0 text-tosca" />
            {{ session('academic_status') }}
        </div>
    @endif

    <div class="mt-6 overflow-x-auto pb-2">
        <div class="inline-flex min-w-max gap-1 rounded-2xl border border-line bg-white p-1.5 shadow-sm">
            @foreach ([
                'years' => ['1', 'Tahun & semester'],
                'classes' => ['2', 'Kelas'],
                'subjects' => ['3', 'Mata pelajaran'],
                'assignments' => ['4', 'Guru pengampu'],
                'students' => ['5', 'Siswa'],
                'schedules' => ['6', 'Jadwal'],
            ] as $key => [$number, $label])
                <button
                    type="button"
                    wire:click="switchTab('{{ $key }}')"
                    @class([
                        'flex min-h-10 items-center gap-2 rounded-xl px-3.5 py-2 text-sm transition',
                        'bg-navy font-semibold text-white shadow-sm' => $tab === $key,
                        'text-ink-soft hover:bg-offwhite hover:text-navy' => $tab !== $key,
                    ])
                >
                    <span @class([
                        'flex size-5 items-center justify-center rounded-full text-[10px] font-semibold',
                        'bg-orange text-navy' => $tab === $key,
                        'bg-tosca-tint text-tosca' => $tab !== $key,
                    ])>{{ $number }}</span>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-card border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($tab === 'years')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-6">
                <x-theme.card>
                    <h2 class="text-lg">{{ $academicYearId ? 'Ubah tahun ajaran' : 'Tambah tahun ajaran' }}</h2>
                    <form wire:submit="saveAcademicYear" class="mt-5 space-y-4">
                        <label class="block text-sm font-semibold text-navy">Label tahun ajaran
                            <input wire:model="yearLabel" type="text" placeholder="2026/2027" class="mt-2 w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm outline-none focus:border-tosca focus:ring-2 focus:ring-tosca/15">
                        </label>
                        <label class="flex items-center gap-3 rounded-xl bg-offwhite p-3 text-sm text-ink-soft">
                            <input wire:model="isActive" type="checkbox" class="size-4 rounded border-line text-tosca focus:ring-tosca">
                            Jadikan tahun ajaran aktif
                        </label>
                        <x-theme.button type="submit" class="w-full">{{ $academicYearId ? 'Simpan perubahan' : 'Tambah tahun ajaran' }}</x-theme.button>
                    </form>
                </x-theme.card>

                <x-theme.card>
                    <h2 class="text-lg">{{ $semesterId ? 'Ubah semester' : 'Tambah semester' }}</h2>
                    <form wire:submit="saveSemester" class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-navy sm:col-span-2">Tahun ajaran
                            <select wire:model="semesterAcademicYearId" class="mt-2 w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                <option value="">Pilih tahun ajaran</option>
                                @foreach ($academicYears as $year)<option value="{{ $year->id }}">{{ $year->year_label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-navy sm:col-span-2">Semester
                            <select wire:model="semesterName" class="mt-2 w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                <option>Ganjil</option><option>Genap</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-navy">Mulai
                            <input wire:model="semesterStartDate" type="date" class="mt-2 w-full rounded-xl border border-line px-3 py-2.5 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-navy">Selesai
                            <input wire:model="semesterEndDate" type="date" class="mt-2 w-full rounded-xl border border-line px-3 py-2.5 text-sm">
                        </label>
                        <x-theme.button type="submit" variant="orange" class="sm:col-span-2">Simpan semester</x-theme.button>
                    </form>
                </x-theme.card>
            </div>

            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-lg">Tahun ajaran tersimpan</h2></div>
                <div class="divide-y divide-line">
                    @forelse ($academicYears as $year)
                        <article class="px-5 py-5 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2"><h3 class="text-lg">{{ $year->year_label }}</h3>@if($year->is_active)<x-theme.badge tone="tosca">Aktif</x-theme.badge>@endif</div>
                                    <p class="mt-1 text-sm text-ink-soft">{{ $year->classes_count }} kelas · {{ $year->semesters->count() }} semester</p>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editAcademicYear({{ $year->id }})" class="rounded-lg p-2 text-ink-soft hover:bg-offwhite hover:text-navy" aria-label="Ubah tahun ajaran"><flux:icon.pencil-square class="size-4" /></button>
                                    <button wire:click="deleteAcademicYear({{ $year->id }})" wire:confirm="Hapus tahun ajaran dan seluruh struktur terkait?" class="rounded-lg p-2 text-red-500 hover:bg-red-50" aria-label="Hapus tahun ajaran"><flux:icon.trash class="size-4" /></button>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                @forelse($year->semesters as $semester)
                                    <div class="flex items-center justify-between rounded-xl bg-offwhite px-3 py-2.5 text-sm">
                                        <span><strong class="text-navy">{{ $semester->name }}</strong><span class="ml-2 text-xs text-ink-soft">{{ $semester->start_date->format('d M Y') }}–{{ $semester->end_date->format('d M Y') }}</span></span>
                                        <span class="flex"><button wire:click="editSemester({{ $semester->id }})" class="p-1.5 text-ink-soft"><flux:icon.pencil class="size-3.5" /></button><button wire:click="deleteSemester({{ $semester->id }})" wire:confirm="Hapus semester ini?" class="p-1.5 text-red-500"><flux:icon.trash class="size-3.5" /></button></span>
                                    </div>
                                @empty
                                    <p class="text-sm text-ink-soft sm:col-span-2">Belum ada semester.</p>
                                @endforelse
                            </div>
                        </article>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Mulai dengan menambahkan tahun ajaran.</div>
                    @endforelse
                </div>
            </x-theme.card>
        </div>
    @elseif ($tab === 'classes')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.7fr_1.3fr]">
            <x-theme.card class="self-start">
                <h2 class="text-lg">{{ $schoolClassId ? 'Ubah kelas' : 'Tambah kelas' }}</h2>
                <form wire:submit="saveSchoolClass" class="mt-5 space-y-4">
                    <label class="block text-sm font-semibold text-navy">Tahun ajaran<select wire:model="classAcademicYearId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih tahun</option>@foreach($academicYears as $year)<option value="{{ $year->id }}">{{ $year->year_label }}</option>@endforeach</select></label>
                    <label class="block text-sm font-semibold text-navy">Nama kelas<input wire:model="className" placeholder="7A" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"></label>
                    <label class="block text-sm font-semibold text-navy">Wali kelas<select wire:model="homeroomTeacherId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Belum ditentukan</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></label>
                    <x-theme.button type="submit" class="w-full">Simpan kelas</x-theme.button>
                </form>
            </x-theme.card>
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b border-line bg-offwhite text-xs uppercase tracking-wider text-ink-soft"><tr><th class="px-5 py-4">Kelas</th><th class="px-5 py-4">Tahun</th><th class="px-5 py-4">Wali kelas</th><th class="px-5 py-4">Siswa</th><th class="px-5 py-4"></th></tr></thead><tbody class="divide-y divide-line">
                    @forelse($classes as $class)<tr><td class="px-5 py-4 font-semibold text-navy">{{ $class->name }}</td><td class="px-5 py-4 text-ink-soft">{{ $class->academicYear->year_label }}</td><td class="px-5 py-4 text-ink-soft">{{ $class->homeroomTeacher?->name ?? '—' }}</td><td class="px-5 py-4"><x-theme.badge tone="tosca-soft">{{ $class->students_count }} siswa</x-theme.badge></td><td class="px-5 py-4"><div class="flex justify-end gap-1"><button wire:click="editSchoolClass({{ $class->id }})" class="p-2 text-ink-soft"><flux:icon.pencil-square class="size-4" /></button><button wire:click="deleteSchoolClass({{ $class->id }})" wire:confirm="Hapus kelas ini?" class="p-2 text-red-500"><flux:icon.trash class="size-4" /></button></div></td></tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-ink-soft">Belum ada kelas.</td></tr>@endforelse
                </tbody></table></div>
            </x-theme.card>
        </div>
    @elseif ($tab === 'subjects')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.7fr_1.3fr]">
            <x-theme.card class="self-start"><h2 class="text-lg">{{ $subjectId ? 'Ubah mata pelajaran' : 'Tambah mata pelajaran' }}</h2><form wire:submit="saveSubject" class="mt-5 space-y-4"><label class="block text-sm font-semibold text-navy">Nama<input wire:model="subjectName" placeholder="Matematika" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"></label><label class="block text-sm font-semibold text-navy">Kode<input wire:model="subjectCode" placeholder="MAT" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm uppercase"></label><x-theme.button type="submit" class="w-full">Simpan mata pelajaran</x-theme.button></form></x-theme.card>
            <div class="grid content-start gap-4 sm:grid-cols-2">@forelse($subjects as $subject)<x-theme.card><div class="flex items-start justify-between gap-3"><div><x-theme.badge tone="tosca-soft">{{ $subject->code }}</x-theme.badge><h3 class="mt-3 text-lg">{{ $subject->name }}</h3></div><div class="flex"><button wire:click="editSubject({{ $subject->id }})" class="p-2 text-ink-soft"><flux:icon.pencil-square class="size-4" /></button><button wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Hapus mata pelajaran ini?" class="p-2 text-red-500"><flux:icon.trash class="size-4" /></button></div></div></x-theme.card>@empty<x-theme.card class="text-sm text-ink-soft sm:col-span-2">Belum ada mata pelajaran.</x-theme.card>@endforelse</div>
        </div>
    @elseif ($tab === 'assignments')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
            <x-theme.card class="self-start"><h2 class="text-lg">Penempatan guru pengampu</h2><p class="mt-1 text-sm text-ink-soft">Satu mapel memiliki satu guru utama per kelas.</p><form wire:submit="saveAssignment" class="mt-5 space-y-4"><label class="block text-sm font-semibold text-navy">Kelas<select wire:model="assignmentClassId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear->year_label }}</option>@endforeach</select></label><label class="block text-sm font-semibold text-navy">Mata pelajaran<select wire:model="assignmentSubjectId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih mapel</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></label><label class="block text-sm font-semibold text-navy">Guru pengampu<select wire:model="assignmentTeacherId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih guru</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></label><x-theme.button type="submit" class="w-full">Simpan penempatan</x-theme.button></form></x-theme.card>
            <x-theme.card :padding="false" class="overflow-hidden"><div class="divide-y divide-line">@forelse($assignments as $assignment)<article class="flex items-center gap-4 px-5 py-4"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca"><flux:icon.book-open class="size-5" /></span><div class="min-w-0 flex-1"><h3 class="truncate text-sm">{{ $assignment->subject->name }} · {{ $assignment->schoolClass->name }}</h3><p class="mt-1 text-xs text-ink-soft">{{ $assignment->teacher->name }} · {{ $assignment->schoolClass->academicYear->year_label }}</p></div><button wire:click="editAssignment({{ $assignment->id }})" class="p-2 text-ink-soft"><flux:icon.pencil-square class="size-4" /></button><button wire:click="deleteAssignment({{ $assignment->id }})" wire:confirm="Hapus penempatan ini? Jadwal terkait juga terhapus." class="p-2 text-red-500"><flux:icon.trash class="size-4" /></button></article>@empty<div class="p-12 text-center text-sm text-ink-soft">Belum ada guru pengampu.</div>@endforelse</div></x-theme.card>
        </div>
    @elseif ($tab === 'students')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
            <x-theme.card class="self-start"><h2 class="text-lg">Masukkan siswa ke kelas</h2><form wire:submit="addStudentToClass" class="mt-5 space-y-4"><label class="block text-sm font-semibold text-navy">Kelas<select wire:model.live="studentClassId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear->year_label }}</option>@endforeach</select></label><label class="block text-sm font-semibold text-navy">Siswa<select wire:model="selectedStudentId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih siswa</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->nisn }}</option>@endforeach</select></label><x-theme.button type="submit" class="w-full">Masukkan siswa</x-theme.button></form></x-theme.card>
            <x-theme.card :padding="false" class="overflow-hidden"><div class="border-b border-line px-5 py-5"><h2 class="text-lg">{{ $selectedClass ? 'Daftar siswa · '.$selectedClass->name : 'Daftar siswa' }}</h2></div><div class="divide-y divide-line">@forelse($selectedClass?->students ?? [] as $student)<article class="flex items-center gap-3 px-5 py-4"><span class="flex size-9 items-center justify-center rounded-full bg-tosca text-xs font-semibold text-white">{{ $student->initials() }}</span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-navy">{{ $student->name }}</p><p class="text-xs text-ink-soft">NISN {{ $student->nisn }}</p></div><button wire:click="removeStudentFromClass({{ $student->id }})" wire:confirm="Keluarkan siswa dari kelas ini?" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Keluarkan</button></article>@empty<div class="p-12 text-center text-sm text-ink-soft">Pilih kelas atau tambahkan siswa.</div>@endforelse</div></x-theme.card>
        </div>
    @elseif ($tab === 'schedules')
        <div class="mt-6 space-y-6">
            <x-theme.card>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                    <label class="block text-sm font-semibold text-navy xl:col-span-2">Kelas<select wire:model.live="scheduleClassId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear->year_label }}</option>@endforeach</select></label>
                    <form wire:submit="saveSchedule" class="contents">
                        <label class="block text-sm font-semibold text-navy xl:col-span-2">Mapel & guru<select wire:model="scheduleClassSubjectId" class="mt-2 w-full rounded-xl border border-line px-3.5 py-2.5 text-sm"><option value="">Pilih pengampu</option>@foreach($scheduleAssignments as $assignment)<option value="{{ $assignment->id }}">{{ $assignment->subject->name }} · {{ $assignment->teacher->name }}</option>@endforeach</select></label>
                        <label class="block text-sm font-semibold text-navy">Hari<select wire:model="scheduleDay" class="mt-2 w-full rounded-xl border border-line px-3 py-2.5 text-sm">@foreach(\App\Livewire\Admin\AcademicSetup::DAYS as $day)<option>{{ $day }}</option>@endforeach</select></label>
                        <div class="grid grid-cols-2 gap-2"><label class="block text-sm font-semibold text-navy">Mulai<input wire:model="scheduleStartTime" type="time" class="mt-2 w-full rounded-xl border border-line px-2 py-2.5 text-sm"></label><label class="block text-sm font-semibold text-navy">Selesai<input wire:model="scheduleEndTime" type="time" class="mt-2 w-full rounded-xl border border-line px-2 py-2.5 text-sm"></label></div>
                        <x-theme.button type="submit" variant="orange" class="md:col-span-2 xl:col-span-6">{{ $scheduleId ? 'Simpan perubahan jadwal' : 'Tambahkan ke jadwal' }}</x-theme.button>
                    </form>
                </div>
            </x-theme.card>

            <div class="overflow-x-auto pb-2"><div class="grid min-w-[980px] grid-cols-6 gap-3">
                @foreach(\App\Livewire\Admin\AcademicSetup::DAYS as $day)
                    <section class="rounded-card border border-line bg-white p-3 shadow-sm"><div class="flex items-center justify-between border-b border-line pb-3"><h3 class="text-sm">{{ $day }}</h3><x-theme.badge tone="neutral">{{ ($schedules[$day] ?? collect())->count() }}</x-theme.badge></div><div class="mt-3 space-y-2">
                        @forelse($schedules[$day] ?? [] as $schedule)
                            <article class="rounded-xl bg-offwhite p-3"><p class="text-xs font-semibold text-tosca">{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</p><p class="mt-1 text-sm font-semibold text-navy">{{ $schedule->classSubject->subject->name }}</p><p class="mt-1 truncate text-xs text-ink-soft">{{ $schedule->classSubject->teacher->name }}</p><div class="mt-2 flex justify-end"><button wire:click="editSchedule({{ $schedule->id }})" class="p-1.5 text-ink-soft"><flux:icon.pencil class="size-3.5" /></button><button wire:click="deleteSchedule({{ $schedule->id }})" wire:confirm="Hapus jadwal ini?" class="p-1.5 text-red-500"><flux:icon.trash class="size-3.5" /></button></div></article>
                        @empty<p class="py-5 text-center text-xs text-ink-soft">Belum ada jadwal</p>@endforelse
                    </div></section>
                @endforeach
            </div></div>
        </div>
    @endif
</div>
