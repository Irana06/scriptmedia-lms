<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $file_path
 * @property int $imported_by
 * @property int $total_rows
 * @property int $success_count
 * @property int $failed_count
 * @property string $status
 * @property array<int, array{row: int, message: string}>|null $failures
 * @property string|null $result_file_path
 * @property Carbon|null $downloaded_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'type', 'file_path', 'imported_by', 'total_rows', 'success_count',
    'failed_count', 'status', 'failures', 'result_file_path', 'downloaded_at',
])]
class DataImport extends Model
{
    protected function casts(): array
    {
        return [
            'failures' => 'array',
            'downloaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
