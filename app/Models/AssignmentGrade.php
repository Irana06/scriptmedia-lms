<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id @property int $submission_id @property string $score @property string|null $feedback */
#[Fillable(['submission_id', 'score', 'feedback'])]
class AssignmentGrade extends Model
{
    /** @return BelongsTo<AssignmentSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class);
    }
}
