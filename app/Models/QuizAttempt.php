<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property int $id @property int $quiz_id @property int $student_id @property Carbon $started_at @property Carbon|null $submitted_at @property string|null $score */
#[Fillable(['quiz_id', 'student_id', 'started_at', 'submitted_at', 'score'])]
class QuizAttempt extends Model
{
    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return HasMany<QuizAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
