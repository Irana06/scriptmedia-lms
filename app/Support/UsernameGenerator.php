<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class UsernameGenerator
{
    /**
     * Username untuk akun tanpa email, dibuat dari nama agar mudah diketik di HP.
     * Alamat internal {username}@{domain} ikut diperiksa supaya tidak bentrok.
     */
    public static function fromName(string $name, string $internalDomain): string
    {
        // Nama dari Dapodik sering membawa gelar setelah koma: "Siti Aminah, S.Pd."
        $base = Str::slug(Str::before($name, ','), '.');
        $base = $base !== '' ? $base : Str::before($internalDomain, '.');
        $candidate = $base;
        $suffix = 2;

        while (User::query()->where('username', $candidate)->orWhere('email', $candidate.'@'.$internalDomain)->exists()) {
            $candidate = $base.$suffix++;
        }

        return $candidate;
    }
}
