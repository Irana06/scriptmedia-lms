<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuizQuestionTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /** @return array<int, list<string>> */
    public function array(): array
    {
        return [
            ['Hasil dari 12 x 8 adalah ...', 'PG', '86', '96', '106', '98', 'B'],
            ['Ibu kota provinsi Jawa Barat adalah ...', 'PG', 'Bandung', 'Semarang', 'Surabaya', 'Serang', 'A'],
            ['Jelaskan proses fotosintesis dengan bahasamu sendiri.', 'Esai', '', '', '', '', ''],
        ];
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['pertanyaan', 'tipe', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'kunci'];
    }
}
