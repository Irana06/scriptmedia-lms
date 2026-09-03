<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $class_subject_id
 * @property string $day
 * @property string $start_time
 * @property string $end_time
 */
#[Fillable(['class_subject_id', 'day', 'start_time', 'end_time'])]
class Schedule extends Model
{
    /** @return BelongsTo<ClassSubject, $this> */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }
}
