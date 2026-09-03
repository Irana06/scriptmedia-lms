<?php

namespace App\Services;

use App\Models\QuizAttempt;

class QuizScoringService
{
    public function recalculate(QuizAttempt $attempt): void
    {
        $attempt->loadMissing('quiz.questions', 'answers');
        $questions = $attempt->quiz->questions;

        if ($questions->isEmpty() || $attempt->submitted_at === null) {
            $attempt->update(['score' => null]);

            return;
        }

        $answers = $attempt->answers->keyBy('question_id');
        $hasPendingEssay = $questions->contains(
            fn ($question): bool => $question->type === 'essay' && $answers->get($question->id)?->score === null,
        );

        if ($hasPendingEssay) {
            $attempt->update(['score' => null]);

            return;
        }

        $earned = $answers->sum(fn ($answer): float => (float) ($answer->score ?? 0));
        $attempt->update(['score' => round(($earned / $questions->count()) * 100, 2)]);
    }
}
