<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportCardController extends Controller
{
    public function __invoke(Semester $semester, User $student): Response
    {
        $schoolClass = SchoolClass::query()
            ->where('academic_year_id', $semester->academic_year_id)
            ->whereHas('students', fn ($query) => $query->whereKey($student->id))
            ->with('homeroomTeacher', 'academicYear')
            ->firstOrFail();
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

        return Pdf::loadView('pdf.report-card', compact('semester', 'student', 'schoolClass', 'grades', 'attendanceCounts'))
            ->setPaper('a4')
            ->download("rapor-{$student->nisn}-semester-".strtolower($semester->name).'.pdf');
    }
}
