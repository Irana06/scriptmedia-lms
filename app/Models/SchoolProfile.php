<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Profil sekolah: satu baris (id 1), tampil di header dashboard guru/admin.
 * Diisi lewat `php artisan sekolah:setup` atau diubah admin.
 *
 * @property int $id
 * @property string $name
 * @property string|null $npsn
 */
#[Fillable(['name', 'npsn'])]
class SchoolProfile extends Model
{
    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], ['name' => 'Sekolah Baru']);
    }
}
