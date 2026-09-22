<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property int $id @property int $class_subject_id @property string $title @property string $category @property string|null $description @property Carbon $deadline */
#[Fillable(['class_subject_id', 'title', 'category', 'description', 'deadline'])]
class Assignment extends Model
{
    /** @var list<string> */
    public const CATEGORIES = ['tugas', 'uts', 'uas'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    /** @return BelongsTo<ClassSubject, $this> */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /** @return HasMany<AssignmentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
