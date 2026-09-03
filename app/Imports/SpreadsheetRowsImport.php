<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithFormatData;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SpreadsheetRowsImport implements SkipsEmptyRows, ToArray, WithFormatData, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @param array<int, array<string, mixed>> $array */
    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
