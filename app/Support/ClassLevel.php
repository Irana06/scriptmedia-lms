<?php

namespace App\Support;

/**
 * Membaca tingkat dari nama kelas ("7A", "VIII-B", "X IPA 1", "Kelas 9C") dan
 * menebak nama kelas di tingkat berikutnya. Hanya dipakai sebagai saran awal
 * di halaman kenaikan kelas; admin tetap bisa mengubah setiap pilihan.
 */
class ClassLevel
{
    private const ROMAN = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
        'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
    ];

    /** @return array{level: int, roman: bool, prefix: string, suffix: string}|null */
    public static function parse(string $name): ?array
    {
        // Tingkat harus di awal nama (setelah kata "Kelas" opsional), supaya angka
        // rombel di belakang seperti "X IPA 1" tidak terbaca sebagai tingkat 1.
        if (preg_match('/^((?:kelas\s+)?)(1[0-2]|[1-9])(?!\d)(.*)$/iu', trim($name), $match) === 1) {
            return ['level' => (int) $match[2], 'roman' => false, 'prefix' => $match[1], 'suffix' => $match[3]];
        }

        // Romawi harus berdiri sendiri (diikuti spasi, tanda baca, atau akhir nama),
        // dan yang terpanjang dicoba lebih dulu supaya "XII" tidak terbaca "XI".
        if (preg_match('/^((?:kelas\s+)?)(XII|XI|IX|X|VIII|VII|VI|IV|V|III|II|I)(?=$|[\s\-_.\/])(.*)$/u', trim($name), $match) === 1) {
            return ['level' => self::ROMAN[$match[2]], 'roman' => true, 'prefix' => $match[1], 'suffix' => $match[3]];
        }

        return null;
    }

    public static function next(string $name): ?string
    {
        $parsed = self::parse($name);

        if ($parsed === null || $parsed['level'] >= 12) {
            return null;
        }

        $level = $parsed['level'] + 1;
        $token = $parsed['roman'] ? (string) array_search($level, self::ROMAN, true) : (string) $level;

        return $parsed['prefix'].$token.$parsed['suffix'];
    }
}
