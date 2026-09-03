<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/** @property int $id @property int $assignment_id @property int $student_id @property string $file_path @property Carbon $submitted_at */
#[Fillable(['assignment_id', 'student_id', 'file_path', 'submitted_at'])]
class AssignmentSubmission extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    /** @return BelongsTo<Assignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return HasOne<AssignmentGrade, $this> */
    public function grade(): HasOne
    {
        return $this->hasOne(AssignmentGrade::class, 'submission_id');
    }
}
