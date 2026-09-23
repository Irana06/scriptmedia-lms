<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportCardController extends Controller
{
    public function __invoke(Request $request, Semester $semester, User $student): Response
    {
        $schoolClass = SchoolClass::query()
            ->where('academic_year_id', $semester->academic_year_id)
            ->whereHas('students', fn ($query) => $query->whereKey($student->id))
            ->with('homeroomTeacher', 'academicYear')
            ->firstOrFail();

        // Admin melihat rapor siapa pun; guru hanya rapor kelas yang diwalikannya.
        $actor = $request->user();
        abort_unless($actor->hasRole('admin') || $schoolClass->homeroom_teacher_id === $actor->id, 403);

        $grades = Grade::query()
            ->with('classSubject.subject', 'classSubject.teacher')
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->sortBy('classSubject.subject.name');
        $attendances = Attendance::query()
            ->where('student_id', $student->id)
            ->where('class_id', $schoolClass->id)
            ->whereBetween('date', [$semester->start_date, $semester->end_date])
            ->get();
        $attendanceCounts = collect(['hadir', 'izin', 'sakit', 'alpa'])
            ->mapWithKeys(fn ($status): array => [$status => $attendances->where('status', $status)->count()]);

        // Siswa sekolah swasta boleh terdaftar dengan NIS saja, tanpa NISN.
        $identifier = $student->nisn ?: ($student->nis ?: $student->id);

        return Pdf::loadView('pdf.report-card', compact('semester', 'student', 'schoolClass', 'grades', 'attendanceCounts'))
            ->setPaper('a4')
            ->download("rapor-{$identifier}-semester-".strtolower($semester->name).'.pdf');
    }
}
