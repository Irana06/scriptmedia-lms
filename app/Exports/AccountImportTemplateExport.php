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
            ? [['Budi Santoso', '0012345678', '3273010101010001', 'L', '7A']]
            : [['Siti Aminah', 'siti.aminah@sekolah.sch.id', '198701012010012001']];
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->type === 'siswa'
            ? ['nama', 'nisn', 'nik', 'jenis_kelamin', 'kelas/rombel']
            : ['nama', 'email', 'nip'];
    }
}
