<?php

namespace App\Support;

use App\Models\User;

class StudentLookup
{
    /**
     * Cari akun siswa dari NISN, NIS, atau username yang dipakai untuk masuk.
     */
    public static function byIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        return User::role('siswa')
            ->where(fn ($query) => $query
                ->where('nisn', $identifier)
                ->orWhere('nis', $identifier)
                ->orWhere('username', $identifier))
            ->first();
    }
}
