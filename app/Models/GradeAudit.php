<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Jejak setiap perubahan nilai: siapa mengubah nilai siapa, dari berapa ke
 * berapa, dan kapan. Hanya ditambah, tidak pernah diubah atau dihapus lewat
 * aplikasi — bukti bila ada keberatan dari orang tua.
 *
 * @property int $id
 * @property string $kind
 * @property int $student_id
 * @property int|null $class_subject_id
 * @property string $item
 * @property string|null $old_score
 * @property string $new_score
 * @property int|null $changed_by
 * @property string $changed_by_name
 * @property Carbon $created_at
 */
#[Fillable(['kind', 'student_id', 'class_subject_id', 'item', 'old_score', 'new_score', 'changed_by', 'changed_by_name'])]
class GradeAudit extends Model
{
    public const UPDATED_AT = null;

    public const KINDS = ['final' => 'Nilai akhir', 'assignment' => 'Nilai tugas', 'essay' => 'Esai kuis'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * Catat hanya bila nilainya benar-benar berubah (menyimpan ulang angka yang
     * sama tidak menambah baris).
     */
    public static function record(string $kind, int $studentId, ?int $classSubjectId, string $item, float|string|null $old, float|string $new): void
    {
        if ($old !== null && round((float) $old, 2) === round((float) $new, 2)) {
            return;
        }

        $actor = Auth::user();

        self::query()->create([
            'kind' => $kind,
            'student_id' => $studentId,
            'class_subject_id' => $classSubjectId,
            'item' => $item,
            'old_score' => $old,
            'new_score' => $new,
            'changed_by' => $actor?->getAuthIdentifier(),
            'changed_by_name' => $actor instanceof User ? $actor->name : 'Sistem',
        ]);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<ClassSubject, $this> */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }
}
