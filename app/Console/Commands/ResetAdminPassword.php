<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Jalan pemulihan bila password admin hilang. Hanya bisa dijalankan dari
 * terminal server (bukan dari aplikasi), jadi hanya pemegang akses server yang
 * bisa memakainya. Password baru acak dan wajib diganti saat login.
 */
class ResetAdminPassword extends Command
{
    protected $signature = 'sekolah:admin-password
        {login? : Email atau username admin; kosongkan untuk melihat daftar admin}';

    protected $description = 'Buat password baru untuk akun admin (pemulihan bila password admin hilang)';

    public function handle(): int
    {
        $admins = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->orderBy('name')->get();
        $login = $this->argument('login');

        if ($login === null) {
            if ($admins->isEmpty()) {
                $this->warn('Belum ada akun admin. Buat dengan `php artisan sekolah:setup`.');

                return self::FAILURE;
            }

            $this->table(['Nama', 'Email', 'Username'], $admins->map(fn (User $admin): array => [$admin->name, $admin->email, $admin->username ?? '-'])->all());
            $this->line('Jalankan lagi dengan email atau username admin yang akan direset.');

            return self::SUCCESS;
        }

        $admin = $admins->first(fn (User $candidate): bool => $candidate->email === $login || $candidate->username === $login);

        if ($admin === null) {
            $this->error("Tidak ada admin dengan email/username {$login}.");

            return self::FAILURE;
        }

        $password = Str::password(14, symbols: false);

        $admin->forceFill(['password' => $password, 'must_change_password' => true])->save();
        // Dicatat di log aplikasi: password_reset_logs mewajibkan akun pelaku, sedangkan
        // perintah ini dijalankan dari terminal server tanpa akun.
        Log::warning('Password admin direset lewat terminal server.', ['admin_id' => $admin->id, 'email' => $admin->email]);

        $this->info("Password baru untuk {$admin->name} ({$admin->email}):");
        $this->line("  {$password}");
        $this->warn('Catat sekarang; tidak ditampilkan lagi. Admin wajib menggantinya saat login.');

        return self::SUCCESS;
    }
}
