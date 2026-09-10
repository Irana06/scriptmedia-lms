<?php

namespace App\Actions;

use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetAccountPassword
{
    /**
     * Reset password sebuah akun dan kembalikan password baru tepat satu kali.
     *
     * Admin boleh mereset siswa dan guru; guru hanya boleh mereset siswa.
     * Akun admin tidak pernah direset lewat jalur ini, dan tidak ada yang
     * mereset akunnya sendiri — itu tugas halaman keamanan akun.
     *
     * @throws AuthorizationException
     */
    public function handle(User $target, User $actor): string
    {
        $allowed = match (true) {
            $actor->is($target), $target->hasRole('admin') => false,
            $target->hasRole('siswa') => $actor->hasAnyRole(['admin', 'guru']),
            $target->hasRole('guru') => $actor->hasRole('admin'),
            default => false,
        };

        if (! $allowed) {
            throw new AuthorizationException;
        }

        $password = Str::password(length: 12, symbols: false);

        DB::transaction(function () use ($target, $actor, $password): void {
            $target->forceFill([
                'password' => $password,
                'must_change_password' => true,
            ])->save();

            PasswordResetLog::query()->create([
                'user_id' => $target->id,
                'reset_by_user_id' => $actor->id,
            ]);
        });

        return $password;
    }
}
