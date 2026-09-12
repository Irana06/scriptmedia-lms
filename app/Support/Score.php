<?php

namespace App\Support;

class Score
{
    /**
     * Tampilkan nilai dengan koma desimal dan tanpa nol di belakang:
     * 88, 91,25, 75,5, 100. Nilai kosong ditampilkan sebagai tanda pisah.
     */
    public static function format(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '–';
        }

        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }
}
