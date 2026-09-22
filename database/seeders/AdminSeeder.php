<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Jangan pernah membuat admin produksi dengan password default publik
        // ("change-this-password" ada di repo). Wizard `sekolah:setup` (password
        // acak, wajib ganti) adalah jalur yang benar untuk instalasi sekolah baru;
        // seeder ini untuk pengembangan lokal.
        if (app()->isProduction() && config('lms.default_admin.password') === 'change-this-password') {
            throw new RuntimeException(
                'LMS_ADMIN_PASSWORD belum diatur di .env server. Jangan jalankan seeder ini di produksi — '
                .'pakai `php artisan sekolah:setup` untuk membuat admin pertama dengan password acak.',
            );
        }

        $admin = User::query()->updateOrCreate(
            ['email' => config('lms.default_admin.email')],
            [
                'name' => config('lms.default_admin.name'),
                'password' => config('lms.default_admin.password'),
                'email_verified_at' => now(),
                'must_change_password' => true,
            ],
        );

        $admin->syncRoles(['admin']);
    }
}
