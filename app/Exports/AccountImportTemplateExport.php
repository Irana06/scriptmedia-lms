<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly string $type) {}

    /** @return array<int, list<string>> */
    public function array(): array
    {
        return $this->type === 'siswa'
            ? [
                ['Budi Santoso', '0012345678', '2024001', '3273010101010001', 'L', '7A'],
                ['Siti Aisyah', '', '2024002', '', 'P', '7A'],
            ]
            : [
                ['Siti Aminah', 'siti.aminah@sekolah.sch.id', '198701012010012001', '1234567890123456'],
                ['Ahmad Fauzi', 'ahmad.fauzi@sekolah.sch.id', '', '6543210987654321'],
                ['Rina Lestari', '', '', ''],
            ];
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->type === 'siswa'
            ? ['nama', 'nisn', 'nis', 'nik', 'jenis_kelamin', 'kelas']
            : ['nama', 'email', 'nip', 'nuptk'];
    }
}
