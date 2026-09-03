<?php

namespace App\Livewire\Teacher;

use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Services\FinalGradeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.guru-admin')]
#[Title('Nilai & Presensi')]
class EvaluationManager extends Component
{
    #[Url]
    public string $tab = 'grades';

    public string $classSubjectId = '';

    public string $semesterId = '';

    /** @var array<int, string> */
    public array $scores = [];

    public string $attendanceClassId = '';

    public string $attendanceDate = '';

    /** @var array<int, string> */
    public array $statuses = [];

    public function mount(): void
    {
        $first = $this->teachingAssignments()->first();
        if ($first) {
            $this->classSubjectId = (string) $first->id;
            $this->attendanceClassId = (string) $first->class_id;
            $semester = Semester::query()->where('academic_year_id', $first->schoolClass->academic_year_id)->orderBy('start_date')->first();
            $this->semesterId = $semester ? (string) $semester->id : '';
        }
        $this->attendanceDate = now()->format('Y-m-d');
        $this->loadScores();
        $this->loadAttendance();
    }

    public function updatedClassSubjectId(): void
    {
        $assignment = $this->selectedAssignment();
        $semester = Semester::query()->where('academic_year_id', $assignment->schoolClass->academic_year_id)->orderBy('start_date')->first();
        $this->semesterId = $semester ? (string) $semester->id : '';
        $this->loadScores();
    }

    public function updatedSemesterId(): void
    {
        $this->loadScores();
    }

    public function updatedAttendanceClassId(): void
    {
        $this->loadAttendance();
    }

    public function updatedAttendanceDate(): void
    {
        $this->loadAttendance();
    }

    public function autoFillScores(FinalGradeService $calculator): void
    {
        $assignment = $this->selectedAssignment()->load('schoolClass.students');
        $semester = Semester::query()->where('academic_year_id', $assignment->schoolClass->academic_year_id)->findOrFail($this->semesterId);
        foreach ($assignment->schoolClass->students as $student) {
            $calculated = $calculator->calculate($student, $assignment, $semester);
            if ($calculated !== null) {
                $this->scores[$student->id] = (string) $calculated;
            }
        }
    }

    public function saveGrades(FinalGradeService $calculator): void
    {
        $assignment = $this->selectedAssignment()->load('schoolClass.students');
        $semester = Semester::query()->where('academic_year_id', $assignment->schoolClass->academic_year_id)->findOrFail($this->semesterId);
        $studentIds = $assignment->schoolClass->students->modelKeys();
        $validated = $this->validate([
            'scores' => ['array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $assignment, $semester, $studentIds, $calculator): void {
            foreach ($validated['scores'] as $studentId => $score) {
                if ($score === '' || $score === null || ! in_array((int) $studentId, $studentIds, true)) {
                    continue;
                }
                $numericScore = (float) $score;
                Grade::query()->updateOrCreate(
                    ['student_id' => $studentId, 'class_subject_id' => $assignment->id, 'semester_id' => $semester->id],
                    ['final_score' => $numericScore, 'predikat' => $calculator->predicate($numericScore)],
                );
            }
        });
        session()->flash('evaluation_status', 'Nilai akhir satu kelas berhasil disimpan.');
    }

    public function saveAttendance(): void
    {
        $class = $this->selectedAttendanceClass()->load('students');
        $validated = $this->validate([
            'attendanceDate' => ['required', 'date'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
        ]);
        $studentIds = $class->students->modelKeys();

        DB::transaction(function () use ($validated, $class, $studentIds): void {
            foreach ($validated['statuses'] as $studentId => $status) {
                if (! in_array((int) $studentId, $studentIds, true)) {
                    continue;
                }
                Attendance::query()->updateOrCreate(
                    ['class_id' => $class->id, 'student_id' => $studentId, 'date' => $validated['attendanceDate']],
                    ['status' => $status],
                );
            }
        });
        session()->flash('evaluation_status', 'Presensi seluruh kelas berhasil disimpan.');
    }

    public function render(): View
    {
        $selectedAssignment = $this->classSubjectId !== ''
            ? $this->teachingAssignments()->with('schoolClass.students', 'subject')->find($this->classSubjectId)
            : null;
        $selectedClass = $this->attendanceClassId !== ''
            ? $this->attendanceClasses()->with('students')->find($this->attendanceClassId)
            : null;
        $semesters = $selectedAssignment
            ? Semester::query()->where('academic_year_id', $selectedAssignment->schoolClass->academic_year_id)->orderBy('start_date')->get()
            : collect();

        return view('livewire.teacher.evaluation-manager', [
            'teachingAssignments' => $this->teachingAssignments()->with('schoolClass', 'subject')->get(),
            'attendanceClasses' => $this->attendanceClasses()->get(),
            'selectedAssignment' => $selectedAssignment,
            'selectedClass' => $selectedClass,
            'semesters' => $semesters,
        ]);
    }

    private function loadScores(): void
    {
        $this->scores = [];
        if ($this->classSubjectId === '' || $this->semesterId === '') {
            return;
        }
        $this->scores = Grade::query()
            ->where('class_subject_id', $this->classSubjectId)
            ->where('semester_id', $this->semesterId)
            ->pluck('final_score', 'student_id')
            ->map(fn ($score): string => (string) $score)->all();
    }

    private function loadAttendance(): void
    {
        $this->statuses = [];
        if ($this->attendanceClassId === '' || $this->attendanceDate === '') {
            return;
        }
        $class = $this->selectedAttendanceClass()->load('students');
        $saved = Attendance::query()->where('class_id', $class->id)->whereDate('date', $this->attendanceDate)->pluck('status', 'student_id');
        foreach ($class->students as $student) {
            $this->statuses[$student->id] = (string) ($saved[$student->id] ?? 'hadir');
        }
    }

    /** @return Builder<ClassSubject> */
    private function teachingAssignments(): Builder
    {
        return ClassSubject::query()->where('teacher_id', Auth::id());
    }

    /** @return Builder<SchoolClass> */
    private function attendanceClasses(): Builder
    {
        return SchoolClass::query()->whereHas('classSubjects', fn ($query) => $query->where('teacher_id', Auth::id()))->orderBy('name');
    }

    private function selectedAssignment(): ClassSubject
    {
        return $this->teachingAssignments()->with('schoolClass')->findOrFail($this->classSubjectId);
    }

    private function selectedAttendanceClass(): SchoolClass
    {
        return $this->attendanceClasses()->findOrFail($this->attendanceClassId);
    }
}
