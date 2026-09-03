<?php

namespace App\Services;

use App\Models\AssignmentGrade;
use App\Models\ClassSubject;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\User;

class FinalGradeService
{
    public function calculate(User $student, ClassSubject $classSubject, Semester $semester): ?float
    {
        $assignmentScores = AssignmentGrade::query()
            ->whereHas('submission', fn ($query) => $query->where('student_id', $student->id))
            ->whereHas('submission.assignment', fn ($query) => $query
                ->where('class_subject_id', $classSubject->id)
                ->whereBetween('deadline', [$semester->start_date, $semester->end_date]))
            ->pluck('score');

        $quizScores = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->whereHas('quiz', fn ($query) => $query
                ->where('class_subject_id', $classSubject->id)
                ->whereBetween('open_at', [$semester->start_date, $semester->end_date]))
            ->pluck('score');

        $scores = $assignmentScores->concat($quizScores)->map(fn ($score): float => (float) $score);

        return $scores->isEmpty() ? null : round($scores->avg(), 2);
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
