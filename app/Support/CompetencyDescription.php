<?php

namespace App\Support;

/**
 * Deskripsi capaian awal untuk rapor (gaya Kurikulum Merdeka), dibuat dari
 * nilai akhir dan KKM mapel. Guru bisa menyunting teksnya sebelum menyimpan.
 */
class CompetencyDescription
{
    public static function generate(string $subject, float $score, int $kkm): string
    {
        return match (true) {
            $score >= max($kkm + 15, 90) => "Menunjukkan penguasaan sangat baik pada seluruh materi {$subject} dan mampu menerapkannya secara mandiri.",
            $score >= $kkm + 5 => "Menunjukkan penguasaan yang baik pada materi {$subject}; perlu terus diasah pada soal-soal penerapan.",
            $score >= $kkm => "Telah mencapai ketuntasan minimal pada materi {$subject}; perlu penguatan pada beberapa materi.",
            default => "Belum mencapai ketuntasan minimal pada materi {$subject}; perlu bimbingan dan mengikuti program remedial.",
        };
    }

    public static function isComplete(float $score, int $kkm): bool
    {
        return $score >= $kkm;
    }
}
