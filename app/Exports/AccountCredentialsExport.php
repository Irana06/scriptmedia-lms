<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountCredentialsExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /** @param array<int, array{name: string, login: string, password: string}> $credentials */
    public function __construct(private readonly array $credentials) {}

    /** @return array<int, array{name: string, login: string, password: string}> */
    public function array(): array
    {
        return $this->credentials;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Nama', 'Username / Email', 'Password Awal'];
    }
}
