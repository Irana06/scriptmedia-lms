<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceRecapExport;
use App\Exports\GradeLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassExportController extends Controller
{
    public function grades(Request $request, Semester $semester, SchoolClass $schoolClass): BinaryFileResponse
    {
        $this->authorizeClass($request, $semester, $schoolClass);

        return Excel::download(new GradeLedgerExport($schoolClass, $semester), $this->filename('leger-nilai', $schoolClass, $semester));
    }

    public function attendance(Request $request, Semester $semester, SchoolClass $schoolClass): BinaryFileResponse
    {
        $this->authorizeClass($request, $semester, $schoolClass);

        return Excel::download(new AttendanceRecapExport($schoolClass, $semester), $this->filename('rekap-presensi', $schoolClass, $semester));
    }

    /** Aturan yang sama dengan rapor: admin semua kelas, guru hanya kelas yang diwalikannya. */
    private function authorizeClass(Request $request, Semester $semester, SchoolClass $schoolClass): void
    {
        abort_unless($schoolClass->academic_year_id === $semester->academic_year_id, 404);

        $actor = $request->user();
        abort_unless($actor->hasRole('admin') || $schoolClass->homeroom_teacher_id === $actor->id, 403);
    }

    private function filename(string $kind, SchoolClass $schoolClass, Semester $semester): string
    {
        return Str::slug("{$kind} {$schoolClass->name} semester {$semester->name}").'.xlsx';
    }
}
