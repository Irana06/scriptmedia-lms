<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_flags_low_attendance_below_kkm_and_missing_assignments(): void
    {
        [$classSubject, $semester, $good, $absent, $weak, $lazy] = $this->context();

        foreach ([$good, $weak, $lazy] as $student) {
            foreach (range(1, 5) as $day) {
                Attendance::query()->create(['class_id' => $classSubject->class_id, 'student_id' => $student->id, 'date' => today()->subDays($day), 'status' => 'hadir']);
            }
        }
        foreach (range(1, 5) as $day) {
            Attendance::query()->create(['class_id' => $classSubject->class_id, 'student_id' => $absent->id, 'date' => today()->subDays($day), 'status' => $day <= 2 ? 'hadir' : 'alpa']);
        }
        Grade::query()->create(['student_id' => $good->id, 'class_subject_id' => $classSubject->id, 'semester_id' => $semester->id, 'final_score' => 90, 'predikat' => 'A']);
        Grade::query()->create(['student_id' => $weak->id, 'class_subject_id' => $classSubject->id, 'semester_id' => $semester->id, 'final_score' => 60, 'predikat' => 'D']);
        foreach (range(1, 3) as $i) {
            $assignment = Assignment::query()->create(['class_subject_id' => $classSubject->id, 'title' => "Tugas {$i}", 'deadline' => now()->subDays($i)]);
            foreach ([$good, $absent, $weak] as $student) {
                $assignment->submissions()->create(['student_id' => $student->id, 'file_path' => 'x.pdf', 'submitted_at' => now()->subDays($i + 1)]);
            }
        }

        $summary = app(EarlyWarningService::class)->summary();
        $flagged = collect($summary['atRisk'])->keyBy('id');

        $this->assertFalse($flagged->has($good->id));
        $this->assertSame(['Kehadiran 40%'], $flagged[$absent->id]['reasons']);
        $this->assertSame(['1 mapel di bawah KKM'], $flagged[$weak->id]['reasons']);
        $this->assertSame(['3 tugas tidak dikumpulkan'], $flagged[$lazy->id]['reasons']);
        $this->assertSame(85, $summary['classStats'][0]['attendanceRate']);
    }

    public function test_monitoring_page_is_admin_only_and_dashboard_links_to_it(): void
    {
        [, , , $absent] = $this->context();
        foreach (range(1, 4) as $day) {
            Attendance::query()->create(['class_id' => $absent->schoolClasses()->value('classes.id'), 'student_id' => $absent->id, 'date' => today()->subDays($day), 'status' => 'alpa']);
        }
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.monitoring.index'))->assertOk()->assertSee($absent->name)->assertSee('Kehadiran 0%');
        $this->get(route('dashboard.admin'))->assertSee('siswa perlu perhatian');
        $this->actingAs(User::factory()->teacher()->create())->get(route('admin.monitoring.index'))->assertForbidden();
    }

    /** @return array{ClassSubject, Semester, User, User, User, User} */
    private function context(): array
    {
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'start_date' => today()->subMonths(2), 'end_date' => today()->addMonths(3)]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $students = User::factory()->count(4)->student(mustChangePassword: false)->create();
        $class->students()->attach($students->pluck('id'));
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT', 'kkm' => 75]);
        $classSubject = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => User::factory()->teacher()->create()->id]);

        return [$classSubject, $semester, ...$students->all()];
    }
}
