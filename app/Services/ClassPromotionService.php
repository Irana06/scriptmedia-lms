<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Memindahkan siswa dari kelas tahun ajaran lama ke kelas tahun ajaran baru.
 * Keanggotaan kelas lama tidak dihapus, jadi rapor dan nilai tahun lalu tetap
 * bisa dibuka.
 */
class ClassPromotionService
{
    public const GRADUATE = 'lulus';

    public const SKIP = '';

    /**
     * @param  array<int, string>  $studentTargets  id siswa => id kelas tujuan | 'lulus' | ''
     * @return array{moved: int, graduated: int, skipped: int}
     */
    public function run(AcademicYear $targetYear, array $studentTargets): array
    {
        $targetClassIds = SchoolClass::query()->where('academic_year_id', $targetYear->id)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $summary = ['moved' => 0, 'graduated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($studentTargets, $targetClassIds, $targetYear, &$summary): void {
            foreach ($studentTargets as $studentId => $target) {
                if ($target === self::SKIP) {
                    $summary['skipped']++;

                    continue;
                }

                $student = User::query()->findOrFail($studentId);

                // Satu siswa hanya boleh di satu kelas per tahun ajaran: bersihkan dulu
                // penempatan di tahun tujuan, supaya menjalankan ulang tidak menggandakan.
                DB::table('class_students')->where('student_id', $student->id)->whereIn('class_id', $targetClassIds)->delete();

                if ($target === self::GRADUATE) {
                    $student->forceFill(['graduated_at' => now()])->save();
                    $summary['graduated']++;

                    continue;
                }

                if (! in_array((int) $target, $targetClassIds, true)) {
                    throw new InvalidArgumentException("Kelas tujuan {$target} bukan milik tahun ajaran {$targetYear->year_label}.");
                }

                DB::table('class_students')->insert([
                    'class_id' => (int) $target,
                    'student_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $student->forceFill(['graduated_at' => null])->save();
                $summary['moved']++;
            }
        });

        return $summary;
    }
}
