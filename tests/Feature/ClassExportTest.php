<?php

namespace Tests\Feature;

use App\Exports\AttendanceRecapExport;
use App\Exports\GradeLedgerExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_ledger_has_one_column_per_subject_and_summary_columns(): void
    {
        [$class, $semester, $students, $math, $science] = $this->context();
        Grade::query()->create(['student_id' => $students[0]->id, 'class_subject_id' => $math->id, 'semester_id' => $semester->id, 'final_score' => 80, 'predikat' => 'B']);
        Grade::query()->create(['student_id' => $students[0]->id, 'class_subject_id' => $science->id, 'semester_id' => $semester->id, 'final_score' => 60, 'predikat' => 'D']);

        $export = new GradeLedgerExport($class, $semester);

        $this->assertSame(['No', 'NISN', 'NIS', 'Nama', 'IPA', 'Matematika', 'Jumlah', 'Rata-rata', 'Mapel di bawah KKM'], $export->headings());
        $row = collect($export->array())->firstWhere(3, $students[0]->name);
        $this->assertSame([60.0, 80.0, 140.0, 70.0, 1], array_slice((array) $row, 4));
    }

    public function test_attendance_recap_counts_statuses_within_the_semester(): void
    {
        [$class, $semester, $students] = $this->context();
        foreach (['hadir', 'hadir', 'hadir', 'sakit'] as $day => $status) {
            Attendance::query()->create(['class_id' => $class->id, 'student_id' => $students[0]->id, 'date' => '2026-08-0'.($day + 1), 'status' => $status]);
        }
        Attendance::query()->create(['class_id' => $class->id, 'student_id' => $students[0]->id, 'date' => '2027-02-01', 'status' => 'alpa']);

        $row = collect((new AttendanceRecapExport($class, $semester))->array())->firstWhere(3, $students[0]->name);

        $this->assertSame([3, 0, 1, 0, 4, 75.0], array_slice((array) $row, 4));
    }

    public function test_downloads_are_limited_to_admin_and_homeroom_teacher(): void
    {
        [$class, $semester] = $this->context();
        $params = ['semester' => $semester, 'schoolClass' => $class];

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.report-cards.ledger', $params))->assertOk()->assertDownload('leger-nilai-7a-semester-ganjil.xlsx');
        $this->actingAs($class->homeroomTeacher()->firstOrFail())->get(route('admin.report-cards.attendance', $params))->assertOk();
        $this->actingAs(User::factory()->teacher()->create())->get(route('admin.report-cards.ledger', $params))->assertForbidden();
    }

    /** @return array{SchoolClass, Semester, list<User>, ClassSubject, ClassSubject} */
    private function context(): array
    {
        $teacher = User::factory()->teacher()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A', 'homeroom_teacher_id' => $teacher->id]);
        $students = User::factory()->count(2)->student(mustChangePassword: false)->create();
        $class->students()->attach($students->pluck('id'));
        $math = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT', 'kkm' => 75])->id, 'teacher_id' => $teacher->id]);
        $science = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => Subject::query()->create(['name' => 'IPA', 'code' => 'IPA', 'kkm' => 70])->id, 'teacher_id' => $teacher->id]);

        return [$class, $semester, $students->all(), $math, $science];
    }
}
