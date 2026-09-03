<?php

namespace App\Livewire\Student;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\QuizScoringService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.siswa')]
#[Title('Kerjakan Kuis')]
class QuizPlayer extends Component
{
    public Quiz $quiz;

    public QuizAttempt $attempt;

    /** @var array<int, string> */
    public array $answers = [];

    public function mount(Quiz $quiz): void
    {
        $this->quiz = $quiz->load('questions.choices', 'classSubject.schoolClass.students');
        abort_unless($this->quiz->classSubject->schoolClass->students->contains('id', Auth::id()), 403);
        abort_if(now()->isBefore($quiz->open_at), 403, 'Kuis belum dibuka.');
        abort_if(now()->isAfter($quiz->close_at), 403, 'Kuis sudah ditutup.');
        abort_if($quiz->questions->isEmpty(), 403, 'Kuis belum memiliki soal.');

        $this->attempt = QuizAttempt::query()->firstOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => Auth::id()],
            ['started_at' => now()],
        );
        $this->answers = $this->attempt->answers()->pluck('answer', 'question_id')->map(fn ($value): string => (string) $value)->all();
    }

    public function submitQuiz(QuizScoringService $scoring): void
    {
        $this->attempt->refresh();
        if ($this->attempt->submitted_at !== null) {
            return;
        }

        $this->quiz->loadMissing('questions.choices');
        DB::transaction(function (): void {
            foreach ($this->quiz->questions as $question) {
                $answerValue = trim($this->answers[$question->id] ?? '');
                $score = null;

                if ($question->type === 'mc') {
                    $choice = $question->choices->firstWhere('id', (int) $answerValue);
                    if ($answerValue !== '' && ! $choice) {
                        throw ValidationException::withMessages(["answers.{$question->id}" => 'Pilihan jawaban tidak valid.']);
                    }
                    $score = $choice?->is_correct ? 1 : 0;
                }

                QuizAnswer::query()->updateOrCreate(
                    ['attempt_id' => $this->attempt->id, 'question_id' => $question->id],
                    ['answer' => $answerValue !== '' ? $answerValue : null, 'score' => $score],
                );
            }
            $this->attempt->update(['submitted_at' => now()]);
        });

        $scoring->recalculate($this->attempt->fresh());
        $this->attempt->refresh();
    }

    public function secondsRemaining(): int
    {
        $durationEnd = Carbon::parse($this->attempt->started_at)->addMinutes($this->quiz->duration_minutes);
        $end = $durationEnd->isBefore($this->quiz->close_at) ? $durationEnd : $this->quiz->close_at;

        return max(0, (int) floor(now()->diffInSeconds($end, false)));
    }

    public function render(): View
    {
        return view('livewire.student.quiz-player', ['secondsRemaining' => $this->secondsRemaining()]);
    }
}
