<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Rules\TeacherScheduleAvailable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.guru-admin')]
#[Title('Struktur Akademik')]
class AcademicSetup extends Component
{
    /** @var list<string> */
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    #[Url]
    public string $tab = 'years';

    public ?int $academicYearId = null;

    public string $yearLabel = '';

    public bool $isActive = false;

    public ?int $semesterId = null;

    public string $semesterAcademicYearId = '';

    public string $semesterName = 'Ganjil';

    public string $semesterStartDate = '';

    public string $semesterEndDate = '';

    public ?int $schoolClassId = null;

    public string $classAcademicYearId = '';

    public string $className = '';

    public string $homeroomTeacherId = '';

    public ?int $subjectId = null;

    public string $subjectName = '';

    public string $subjectCode = '';

    public ?int $assignmentId = null;

    public string $assignmentClassId = '';

    public string $assignmentSubjectId = '';

    public string $assignmentTeacherId = '';

    public string $studentClassId = '';

    public string $selectedStudentId = '';

    public ?int $scheduleId = null;

    public string $scheduleClassId = '';

    public string $scheduleClassSubjectId = '';

    public string $scheduleDay = 'Senin';

    public string $scheduleStartTime = '';

    public string $scheduleEndTime = '';

    public function mount(): void
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        if ($activeYear) {
            $this->semesterAcademicYearId = (string) $activeYear->id;
            $this->classAcademicYearId = (string) $activeYear->id;
        }

        $firstClass = SchoolClass::query()->orderBy('name')->first();

        if ($firstClass) {
            $this->studentClassId = (string) $firstClass->id;
            $this->scheduleClassId = (string) $firstClass->id;
        }
    }

    public function switchTab(string $tab): void
    {
        abort_unless(in_array($tab, ['years', 'classes', 'subjects', 'assignments', 'students', 'schedules'], true), 404);
        $this->tab = $tab;
        $this->resetValidation();
    }

    public function saveAcademicYear(): void
    {
        $validated = $this->validate([
            'yearLabel' => ['required', 'string', 'max:20', Rule::unique('academic_years', 'year_label')->ignore($this->academicYearId)],
            'isActive' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            if ($validated['isActive']) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            AcademicYear::query()->updateOrCreate(
                ['id' => $this->academicYearId],
                ['year_label' => $validated['yearLabel'], 'is_active' => $validated['isActive']],
            );
        });

        $this->resetAcademicYearForm();
        $this->notify('Tahun ajaran berhasil disimpan.');
    }

    public function editAcademicYear(int $id): void
    {
        $year = AcademicYear::query()->findOrFail($id);
        $this->academicYearId = $year->id;
        $this->yearLabel = $year->year_label;
        $this->isActive = $year->is_active;
    }

    public function deleteAcademicYear(int $id): void
    {
        AcademicYear::query()->findOrFail($id)->delete();
        $this->resetAcademicYearForm();
        $this->notify('Tahun ajaran beserta struktur terkait dihapus.');
    }

    public function saveSemester(): void
    {
        $validated = $this->validate([
            'semesterAcademicYearId' => ['required', 'exists:academic_years,id'],
            'semesterName' => ['required', Rule::in(['Ganjil', 'Genap']), Rule::unique('semesters', 'name')->where('academic_year_id', $this->semesterAcademicYearId)->ignore($this->semesterId)],
            'semesterStartDate' => ['required', 'date'],
            'semesterEndDate' => ['required', 'date', 'after:semesterStartDate'],
        ]);

        Semester::query()->updateOrCreate(
            ['id' => $this->semesterId],
            [
                'academic_year_id' => $validated['semesterAcademicYearId'],
                'name' => $validated['semesterName'],
                'start_date' => $validated['semesterStartDate'],
                'end_date' => $validated['semesterEndDate'],
            ],
        );

        $this->resetSemesterForm();
        $this->notify('Semester berhasil disimpan.');
    }

    public function editSemester(int $id): void
    {
        $semester = Semester::query()->findOrFail($id);
        $this->semesterId = $semester->id;
        $this->semesterAcademicYearId = (string) $semester->academic_year_id;
        $this->semesterName = $semester->name;
        $this->semesterStartDate = $semester->start_date->format('Y-m-d');
        $this->semesterEndDate = $semester->end_date->format('Y-m-d');
    }

    public function deleteSemester(int $id): void
    {
        Semester::query()->findOrFail($id)->delete();
        $this->resetSemesterForm();
        $this->notify('Semester berhasil dihapus.');
    }

    public function saveSchoolClass(): void
    {
        $validated = $this->validate([
            'classAcademicYearId' => ['required', 'exists:academic_years,id'],
            'className' => ['required', 'string', 'max:30', Rule::unique('classes', 'name')->where('academic_year_id', $this->classAcademicYearId)->ignore($this->schoolClassId)],
            'homeroomTeacherId' => ['nullable', 'exists:users,id'],
        ]);

        if ($validated['homeroomTeacherId'] !== '' && ! User::query()->whereKey($validated['homeroomTeacherId'])->firstOrFail()->hasRole('guru')) {
            throw ValidationException::withMessages(['homeroomTeacherId' => 'Wali kelas harus memiliki role guru.']);
        }

        $schoolClass = SchoolClass::query()->updateOrCreate(
            ['id' => $this->schoolClassId],
            [
                'academic_year_id' => $validated['classAcademicYearId'],
                'name' => $validated['className'],
                'homeroom_teacher_id' => $validated['homeroomTeacherId'] ?: null,
            ],
        );

        if ($this->studentClassId === '') {
            $this->studentClassId = (string) $schoolClass->id;
        }

        if ($this->scheduleClassId === '') {
            $this->scheduleClassId = (string) $schoolClass->id;
        }
        $this->resetSchoolClassForm();
        $this->notify('Kelas berhasil disimpan.');
    }

    public function editSchoolClass(int $id): void
    {
        $class = SchoolClass::query()->findOrFail($id);
        $this->schoolClassId = $class->id;
        $this->classAcademicYearId = (string) $class->academic_year_id;
        $this->className = $class->name;
        $this->homeroomTeacherId = (string) ($class->homeroom_teacher_id ?? '');
    }

    public function deleteSchoolClass(int $id): void
    {
        SchoolClass::query()->findOrFail($id)->delete();
        $this->resetSchoolClassForm();
        $this->notify('Kelas beserta penempatan dan jadwalnya dihapus.');
    }

    public function saveSubject(): void
    {
        $validated = $this->validate([
            'subjectName' => ['required', 'string', 'max:100'],
            'subjectCode' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($this->subjectId)],
        ]);

        Subject::query()->updateOrCreate(
            ['id' => $this->subjectId],
            ['name' => $validated['subjectName'], 'code' => strtoupper($validated['subjectCode'])],
        );

        $this->resetSubjectForm();
        $this->notify('Mata pelajaran berhasil disimpan.');
    }

    public function editSubject(int $id): void
    {
        $subject = Subject::query()->findOrFail($id);
        $this->subjectId = $subject->id;
        $this->subjectName = $subject->name;
        $this->subjectCode = $subject->code;
    }

    public function deleteSubject(int $id): void
    {
        Subject::query()->findOrFail($id)->delete();
        $this->resetSubjectForm();
        $this->notify('Mata pelajaran berhasil dihapus.');
    }

    public function saveAssignment(): void
    {
        $validated = $this->validate([
            'assignmentClassId' => ['required', 'exists:classes,id'],
            'assignmentSubjectId' => ['required', 'exists:subjects,id', Rule::unique('class_subjects', 'subject_id')->where('class_id', $this->assignmentClassId)->ignore($this->assignmentId)],
            'assignmentTeacherId' => ['required', 'exists:users,id'],
        ]);

        if (! User::query()->whereKey($validated['assignmentTeacherId'])->firstOrFail()->hasRole('guru')) {
            throw ValidationException::withMessages(['assignmentTeacherId' => 'Pengampu harus memiliki role guru.']);
        }

        ClassSubject::query()->updateOrCreate(
            ['id' => $this->assignmentId],
            [
                'class_id' => $validated['assignmentClassId'],
                'subject_id' => $validated['assignmentSubjectId'],
                'teacher_id' => $validated['assignmentTeacherId'],
            ],
        );

        $this->resetAssignmentForm();
        $this->notify('Guru pengampu berhasil ditempatkan.');
    }

    public function editAssignment(int $id): void
    {
        $assignment = ClassSubject::query()->findOrFail($id);
        $this->assignmentId = $assignment->id;
        $this->assignmentClassId = (string) $assignment->class_id;
        $this->assignmentSubjectId = (string) $assignment->subject_id;
        $this->assignmentTeacherId = (string) $assignment->teacher_id;
    }

    public function deleteAssignment(int $id): void
    {
        ClassSubject::query()->findOrFail($id)->delete();
        $this->resetAssignmentForm();
        $this->notify('Penempatan guru beserta jadwal terkait dihapus.');
    }

    public function addStudentToClass(): void
    {
        $validated = $this->validate([
            'studentClassId' => ['required', 'exists:classes,id'],
            'selectedStudentId' => ['required', 'exists:users,id'],
        ]);

        $class = SchoolClass::query()->whereKey($validated['studentClassId'])->firstOrFail();
        $student = User::query()->whereKey($validated['selectedStudentId'])->firstOrFail();

        if (! $student->hasRole('siswa')) {
            throw ValidationException::withMessages(['selectedStudentId' => 'Pengguna yang dipilih bukan siswa.']);
        }

        $alreadyPlaced = $student->schoolClasses()
            ->where('academic_year_id', $class->academic_year_id)
            ->whereKeyNot($class->id)
            ->exists();

        if ($alreadyPlaced) {
            throw ValidationException::withMessages(['selectedStudentId' => 'Siswa sudah ditempatkan di kelas lain pada tahun ajaran ini.']);
        }

        $class->students()->syncWithoutDetaching([$student->id]);
        $this->selectedStudentId = '';
        $this->notify('Siswa berhasil ditempatkan ke kelas.');
    }

    public function removeStudentFromClass(int $studentId): void
    {
        SchoolClass::query()->findOrFail($this->studentClassId)->students()->detach($studentId);
        $this->notify('Siswa dikeluarkan dari kelas.');
    }

    public function saveSchedule(): void
    {
        $base = $this->validate([
            'scheduleClassId' => ['required', 'exists:classes,id'],
            'scheduleClassSubjectId' => [
                'required',
                Rule::exists('class_subjects', 'id')->where('class_id', $this->scheduleClassId),
            ],
            'scheduleDay' => ['required', Rule::in(self::DAYS)],
            'scheduleStartTime' => ['required', 'date_format:H:i'],
            'scheduleEndTime' => ['required', 'date_format:H:i', 'after:scheduleStartTime'],
        ]);

        $this->validate([
            'scheduleEndTime' => [
                new TeacherScheduleAvailable(
                    (int) $base['scheduleClassSubjectId'],
                    $base['scheduleDay'],
                    $base['scheduleStartTime'],
                    $this->scheduleId,
                ),
            ],
        ]);

        Schedule::query()->updateOrCreate(
            ['id' => $this->scheduleId],
            [
                'class_subject_id' => $base['scheduleClassSubjectId'],
                'day' => $base['scheduleDay'],
                'start_time' => $base['scheduleStartTime'],
                'end_time' => $base['scheduleEndTime'],
            ],
        );

        $this->resetScheduleForm();
        $this->notify('Jadwal pelajaran berhasil disimpan.');
    }

    public function editSchedule(int $id): void
    {
        $schedule = Schedule::query()->with('classSubject')->findOrFail($id);
        $this->scheduleId = $schedule->id;
        $this->scheduleClassId = (string) $schedule->classSubject->class_id;
        $this->scheduleClassSubjectId = (string) $schedule->class_subject_id;
        $this->scheduleDay = $schedule->day;
        $this->scheduleStartTime = substr($schedule->start_time, 0, 5);
        $this->scheduleEndTime = substr($schedule->end_time, 0, 5);
    }

    public function deleteSchedule(int $id): void
    {
        Schedule::query()->findOrFail($id)->delete();
        $this->resetScheduleForm();
        $this->notify('Jadwal pelajaran berhasil dihapus.');
    }

    public function updatedScheduleClassId(): void
    {
        $this->scheduleClassSubjectId = '';
    }

    public function render(): View
    {
        $academicYears = AcademicYear::query()->withCount('classes')->with('semesters')->orderByDesc('year_label')->get();
        $teachers = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'guru'))
            ->orderBy('name')
            ->get();
        $students = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'siswa'))
            ->orderBy('name')
            ->get();
        $classes = SchoolClass::query()->with(['academicYear', 'homeroomTeacher'])->withCount('students')->orderBy('name')->get();
        $subjects = Subject::query()->orderBy('name')->get();
        $assignments = ClassSubject::query()->with(['schoolClass.academicYear', 'subject', 'teacher'])->orderBy('class_id')->get();
        $selectedClass = $this->studentClassId ? SchoolClass::query()->with('students')->find($this->studentClassId) : null;
        $scheduleAssignments = $this->scheduleClassId
            ? ClassSubject::query()->with(['subject', 'teacher'])->where('class_id', $this->scheduleClassId)->get()
            : collect();
        $schedules = $this->scheduleClassId
            ? Schedule::query()->with(['classSubject.subject', 'classSubject.teacher'])
                ->whereHas('classSubject', fn (Builder $query) => $query->where('class_id', $this->scheduleClassId))
                ->orderBy('start_time')
                ->get()
                ->groupBy('day')
            : collect();

        return view('livewire.admin.academic-setup', compact(
            'academicYears',
            'teachers',
            'students',
            'classes',
            'subjects',
            'assignments',
            'selectedClass',
            'scheduleAssignments',
            'schedules',
        ));
    }

    private function resetAcademicYearForm(): void
    {
        $this->reset('academicYearId', 'yearLabel', 'isActive');
        $this->resetValidation();
    }

    private function resetSemesterForm(): void
    {
        $this->reset('semesterId', 'semesterName', 'semesterStartDate', 'semesterEndDate');
        $this->semesterName = 'Ganjil';
        $this->resetValidation();
    }

    private function resetSchoolClassForm(): void
    {
        $this->reset('schoolClassId', 'className', 'homeroomTeacherId');
        $this->resetValidation();
    }

    private function resetSubjectForm(): void
    {
        $this->reset('subjectId', 'subjectName', 'subjectCode');
        $this->resetValidation();
    }

    private function resetAssignmentForm(): void
    {
        $this->reset('assignmentId', 'assignmentClassId', 'assignmentSubjectId', 'assignmentTeacherId');
        $this->resetValidation();
    }

    private function resetScheduleForm(): void
    {
        $this->reset('scheduleId', 'scheduleClassSubjectId', 'scheduleStartTime', 'scheduleEndTime');
        $this->scheduleDay = 'Senin';
        $this->resetValidation();
    }

    private function notify(string $message): void
    {
        session()->flash('academic_status', $message);
    }
}
