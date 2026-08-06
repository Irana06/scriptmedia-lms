<?php

namespace App\Actions;

use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetStudentPassword
{
    /**
     * Reset a student's password and return the plain password exactly once.
     *
     * @throws AuthorizationException
     */
    public function handle(User $student, User $actor): string
    {
        if (! $actor->hasAnyRole(['admin', 'guru']) || ! $student->hasRole('siswa')) {
            throw new AuthorizationException;
        }

        $password = Str::password(length: 12, symbols: false);

        DB::transaction(function () use ($student, $actor, $password): void {
            $student->forceFill([
                'password' => $password,
                'must_change_password' => true,
            ])->save();

            PasswordResetLog::query()->create([
                'user_id' => $student->id,
                'reset_by_user_id' => $actor->id,
            ]);
        });

        return $password;
    }
}
