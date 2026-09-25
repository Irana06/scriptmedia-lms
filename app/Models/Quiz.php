<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $class_subject_id
 * @property string $title
 * @property string $category
 * @property int $duration_minutes
 * @property Carbon $open_at
 * @property Carbon $close_at
 * @property bool $shuffle
 */
#[Fillable(['class_subject_id', 'title', 'category', 'duration_minutes', 'open_at', 'close_at', 'shuffle'])]
class Quiz extends Model
{
    /** @var list<string> */
    public const CATEGORIES = ['kuis', 'uts', 'uas'];

    protected function casts(): array
    {
        return ['open_at' => 'datetime', 'close_at' => 'datetime', 'shuffle' => 'boolean'];
    }

    /** @return BelongsTo<ClassSubject, $this> */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /** @return HasMany<QuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
