<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Satu-satunya tempat yang memutuskan data siswa siapa yang boleh dilihat,
 * dan siapa yang boleh bertindak sebagai siswa.
 *
 * Dua jalur ini sengaja dipisah. Orang tua boleh melihat data anak yang
 * tautannya sudah disetujui admin, tetapi tidak pernah boleh mengumpulkan
 * tugas atau mengerjakan kuis atas nama anak: actingStudent() hanya menerima
 * siswa yang sedang login.
 */
class StudentAccess
{
    /** Kunci sesi untuk anak yang sedang dipilih akun orang tua. */
    public const SELECTED_CHILD = 'guardian.selected_child_id';

    /**
     * Siswa yang datanya sedang ditampilkan: nilai, presensi, aktivitas, jadwal.
     */
    public function viewedStudent(): User
    {
        $user = $this->authenticatedUser();

        if ($user->hasRole('siswa')) {
            return $user;
        }

        if ($user->hasRole('ortu')) {
            $child = $user->children()->whereKey((int) session(self::SELECTED_CHILD, 0))->first();

            abort_unless($child instanceof User, 403);

            return $child;
        }

        abort(403);
    }

    /**
     * Siswa yang sedang melakukan aksi: mengumpulkan tugas, mengerjakan kuis.
     * Tidak pernah orang tua, meskipun tautannya sudah disetujui.
     */
    public function actingStudent(): User
    {
        $user = $this->authenticatedUser();

        abort_unless($user->hasRole('siswa'), 403);

        return $user;
    }

    /**
     * Apakah $viewer boleh melihat data milik siswa $studentId.
     */
    public function canView(User $viewer, int $studentId): bool
    {
        if ($viewer->hasRole('siswa')) {
            return $viewer->id === $studentId;
        }

        if ($viewer->hasRole('ortu')) {
            return $viewer->children()->whereKey($studentId)->exists();
        }

        return false;
    }

    /**
     * Pilih anak yang datanya akan ditampilkan untuk akun orang tua.
     */
    public function selectChild(User $guardian, int $studentId): void
    {
        abort_unless($guardian->hasRole('ortu') && $this->canView($guardian, $studentId), 403);

        session()->put(self::SELECTED_CHILD, $studentId);
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
