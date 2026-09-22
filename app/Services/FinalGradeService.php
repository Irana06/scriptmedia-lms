<?php

namespace App\Services;

use App\Models\AssignmentGrade;
use App\Models\ClassSubject;
use App\Models\GradeWeightSetting;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;

class FinalGradeService
{
    /**
     * Nilai akhir berbobot per kategori (tugas/kuis/UTS/UAS). Kategori yang tidak
     * punya nilai sama sekali ditiadakan, dan bobotnya dibagi proporsional ke
     * kategori yang terisi — supaya guru yang tidak memberi kuis, misalnya, tidak
     * membuat siswa kehilangan 20% nilai begitu saja.
     */
    public function calculate(User $student, ClassSubject $classSubject, Semester $semester): ?float
    {
        $categoryScores = $this->categoryAverages($student, $classSubject, $semester);
        $weights = GradeWeightSetting::current()->toWeights();

        $present = collect($weights)->only($categoryScores->keys())->filter(fn (float $weight): bool => $weight > 0);

        if ($present->isEmpty()) {
            return null;
        }

        $totalWeight = $present->sum();
        $weighted = 0.0;

        foreach ($present as $category => $weight) {
            $weighted += $categoryScores[$category] * ($weight / $totalWeight);
        }

        return round($weighted, 2);
    }

    /** @return Collection<string, float> nilai rata-rata per kategori yang punya data */
    private function categoryAverages(User $student, ClassSubject $classSubject, Semester $semester): Collection
    {
        $assignmentScores = AssignmentGrade::query()
            ->whereHas('submission', fn ($query) => $query->where('student_id', $student->id))
            ->whereHas('submission.assignment', fn ($query) => $query
                ->where('class_subject_id', $classSubject->id)
                ->whereBetween('deadline', [$semester->start_date, $semester->end_date]))
            ->join('assignment_submissions', 'assignment_submissions.id', '=', 'assignment_grades.submission_id')
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->get(['assignment_grades.score', 'assignments.category']);

        $quizScores = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->whereHas('quiz', fn ($query) => $query
                ->where('class_subject_id', $classSubject->id)
                ->whereBetween('open_at', [$semester->start_date, $semester->end_date]))
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->get(['quiz_attempts.score', 'quizzes.category']);

        return $assignmentScores->concat($quizScores)
            ->groupBy('category')
            ->map(fn (Collection $scores): float => (float) $scores->avg('score'));
    }

    public function predicate(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }
}
