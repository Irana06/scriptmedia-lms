<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Satu-satunya tempat yang memutuskan data siswa siapa yang boleh dilihat,
 * dan siapa yang boleh bertindak sebagai siswa.
 *
 * Dua jalur ini sengaja dipisah walau hari ini isinya sama. Modul orang tua
 * kelak hanya boleh memperluas viewedStudent() dan canView(): orang tua boleh
 * melihat data anaknya, tetapi tidak pernah boleh mengumpulkan tugas atau
 * mengerjakan kuis atas nama anak. actingStudent() harus tetap hanya menerima
 * siswa yang sedang login.
 */
class StudentAccess
{
    /**
     * Siswa yang datanya sedang ditampilkan: nilai, presensi, aktivitas, jadwal.
     */
    public function viewedStudent(): User
    {
        return $this->authenticatedStudent();
    }

    /**
     * Siswa yang sedang melakukan aksi: mengumpulkan tugas, mengerjakan kuis.
     */
    public function actingStudent(): User
    {
        return $this->authenticatedStudent();
    }

    /**
     * Apakah $viewer boleh melihat data milik siswa $studentId.
     */
    public function canView(User $viewer, int $studentId): bool
    {
        return $viewer->id === $studentId && $viewer->hasRole('siswa');
    }

    private function authenticatedStudent(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->hasRole('siswa'), 403);

        return $user;
    }
}
