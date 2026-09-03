<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id @property int $question_id @property string $label @property bool $is_correct */
#[Fillable(['question_id', 'label', 'is_correct'])]
class QuizChoice extends Model
{
    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    /** @return BelongsTo<QuizQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
