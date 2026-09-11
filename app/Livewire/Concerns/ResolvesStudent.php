<?php

namespace App\Livewire\Concerns;

use App\Auth\StudentAccess;

/**
 * Komponen siswa memakai viewedStudentId() untuk membaca data dan
 * actingStudentId() untuk aksi. Jangan memakai Auth::id() langsung —
 * alasannya ada di StudentAccess.
 */
trait ResolvesStudent
{
    protected function viewedStudentId(): int
    {
        return app(StudentAccess::class)->viewedStudent()->id;
    }

    protected function actingStudentId(): int
    {
        return app(StudentAccess::class)->actingStudent()->id;
    }
}
