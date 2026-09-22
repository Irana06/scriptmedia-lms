<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Bobot nilai akhir sekolah: satu baris (id 1), diatur admin di Struktur Akademik.
 *
 * @property int $id
 * @property float $tugas
 * @property float $kuis
 * @property float $uts
 * @property float $uas
 */
#[Fillable(['tugas', 'kuis', 'uts', 'uas'])]
class GradeWeightSetting extends Model
{
    /** @var list<string> */
    public const CATEGORIES = ['tugas', 'kuis', 'uts', 'uas'];

    public static function current(): self
    {
        return self::query()->findOrFail(1);
    }

    /** @return array<string, float> */
    public function toWeights(): array
    {
        return [
            'tugas' => (float) $this->tugas,
            'kuis' => (float) $this->kuis,
            'uts' => (float) $this->uts,
            'uas' => (float) $this->uas,
        ];
    }
}
