<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property int $id @property int $class_subject_id @property string $title @property string|null $description @property int $order */
#[Fillable(['class_subject_id', 'title', 'description', 'order'])]
class Material extends Model
{
    /** @return BelongsTo<ClassSubject, $this> */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /** @return HasMany<MaterialFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(MaterialFile::class);
    }
}
