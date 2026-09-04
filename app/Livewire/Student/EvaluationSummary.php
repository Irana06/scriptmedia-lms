<?php

namespace App\Livewire\Student;

use App\Models\AssignmentGrade;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\QuizAttempt;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.siswa')]
#[Title('Nilai & Presensi')]
class EvaluationSummary extends Component
{
    #[Url]
    public string $semesterId = '';

    #[Url]
    public string $classSubjectId = '';

    public function mount(): void
    {
        if ($this->semesterId === '') {
            $semester = Semester::query()->whereHas('academicYear', fn ($query) => $query->where('is_active', true))->orderBy('start_date')->first()
                ?? Semester::query()->latest('start_date')->first();
            $this->semesterId = $semester ? (string) $semester->id : '';
        }
    }

    public function render(): View
    {
        $semester = $this->semesterId !== '' ? Semester::query()->with('academicYear')->find($this->semesterId) : null;
        $grades = $semester
            ? Grade::query()->with('classSubject.subject', 'classSubject.teacher')->where('student_id', Auth::id())->where('semester_id', $semester->id)->get()
            : collect();
        $selectedGrade = $this->classSubjectId !== ''
            ? $grades->firstWhere('class_subject_id', (int) $this->classSubjectId)
            : null;
        $attendance = $semester
            ? Attendance::query()->where('student_id', Auth::id())->whereBetween('date', [$semester->start_date, $semester->end_date])->get()
            : collect();
        $presentCount = $attendance->where('status', 'hadir')->count();
        $assignmentScores = $selectedGrade && $semester
            ? AssignmentGrade::query()
                ->with('submission.assignment')
                ->whereHas('submission', fn ($query) => $query->where('student_id', Auth::id()))
                ->whereHas('submission.assignment', fn ($query) => $query
                    ->where('class_subject_id', $selectedGrade->class_subject_id)
                    ->whereBetween('deadline', [$semester->start_date, $semester->end_date]))
                ->get()
            : collect();
        $quizScores = $selectedGrade && $semester
            ? QuizAttempt::query()
                ->with('quiz')
                ->where('student_id', Auth::id())
                ->whereNotNull('submitted_at')
                ->whereHas('quiz', fn ($query) => $query
                    ->where('class_subject_id', $selectedGrade->class_subject_id)
                    ->whereBetween('open_at', [$semester->start_date, $semester->end_date]))
                ->get()
            : collect();
        $sourceScores = $assignmentScores->pluck('score')
            ->concat($quizScores->pluck('score')->filter(fn ($score): bool => $score !== null))
            ->map(fn ($score): float => (float) $score);

        return view('livewire.student.evaluation-summary', [
            'semesters' => Semester::query()->with('academicYear')->latest('start_date')->get(),
            'semester' => $semester,
            'grades' => $grades,
            'attendanceCounts' => collect(['hadir', 'izin', 'sakit', 'alpa'])->mapWithKeys(fn ($status): array => [$status => $attendance->where('status', $status)->count()]),
            'attendanceRecords' => $attendance->sortByDesc('date')->take(30),
            'attendanceRate' => $attendance->isNotEmpty() ? (int) round($presentCount / $attendance->count() * 100) : 0,
            'selectedGrade' => $selectedGrade,
            'assignmentScores' => $assignmentScores,
            'quizScores' => $quizScores,
            'sourceAverage' => $sourceScores->isNotEmpty() ? round($sourceScores->avg(), 2) : null,
        ]);
    }
}
