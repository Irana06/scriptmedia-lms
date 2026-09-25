<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeWeightSetting;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Langkah persiapan sekolah baru untuk kartu "Siapkan sekolah Anda" di
 * dashboard admin. Setiap langkah dicek dari data sungguhan, bukan dicentang
 * manual, sehingga tidak bisa "selesai" padahal datanya belum ada.
 */
class SetupChecklist
{
    /** @return Collection<int, array{label: string, hint: string, done: bool, url: string}> */
    public static function steps(): Collection
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $activeClasses = fn (): Builder => SchoolClass::query()->where('academic_year_id', $activeYear?->id);
        $weights = GradeWeightSetting::query()->find(1);

        return collect([
            [
                'label' => 'Lengkapi profil & logo sekolah',
                'hint' => 'Tampil di halaman masuk dan kop rapor.',
                'done' => SchoolProfile::current()->isConfigured() && SchoolProfile::current()->logo_path !== null,
                'url' => route('admin.school.index'),
            ],
            [
                'label' => 'Tahun ajaran aktif & semester',
                'hint' => 'Semester Ganjil dan Genap beserta tanggalnya.',
                'done' => $activeYear !== null && Semester::query()->where('academic_year_id', $activeYear->id)->exists(),
                'url' => route('admin.academic.index', ['tab' => 'years']),
            ],
            [
                'label' => 'Buat kelas',
                'hint' => 'Beserta wali kelasnya.',
                'done' => $activeYear !== null && $activeClasses()->exists(),
                'url' => route('admin.academic.index', ['tab' => 'classes']),
            ],
            [
                'label' => 'Impor akun guru & siswa',
                'hint' => 'Langsung dari berkas Excel Dapodik/EMIS.',
                'done' => $activeYear !== null && $activeClasses()->whereHas('students')->exists(),
                'url' => route('admin.imports.index'),
            ],
            [
                'label' => 'Tetapkan guru pengampu',
                'hint' => 'Guru mana mengajar mapel apa di kelas mana.',
                'done' => $activeYear !== null && ClassSubject::query()->whereIn('class_id', $activeClasses()->select('id'))->exists(),
                'url' => route('admin.academic.index', ['tab' => 'assignments']),
            ],
            [
                'label' => 'Susun jadwal pelajaran',
                'hint' => 'Jadwal bentrok otomatis ditolak.',
                'done' => $activeYear !== null && Schedule::query()->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $activeClasses()->select('id')))->exists(),
                'url' => route('admin.academic.index', ['tab' => 'schedules']),
            ],
            [
                'label' => 'Tinjau bobot nilai akhir',
                'hint' => 'Tugas, kuis, UTS, dan UAS sesuai kebijakan sekolah.',
                'done' => $weights !== null && $weights->updated_at?->gt($weights->created_at) === true,
                'url' => route('admin.academic.index', ['tab' => 'weights']),
            ],
        ]);
    }
}
