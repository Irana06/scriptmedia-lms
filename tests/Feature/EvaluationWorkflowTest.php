<?php

namespace Tests\Feature;

use App\Livewire\Student\EvaluationSummary;
use App\Livewire\Teacher\EvaluationManager;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_calculate_then_manually_edit_final_scores(): void
    {
        [$teacher, $students, $classSubject, $semester] = $this->evaluationContext();
        $student = $students[0];
        $assignment = Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas Semester',
            'deadline' => '2026-09-10 12:00:00',
        ]);
        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'file_path' => 'testing/jawaban.pdf',
            'submitted_at' => '2026-09-09 10:00:00',
        ]);
        AssignmentGrade::query()->create(['submission_id' => $submission->id, 'score' => 90]);
        $quiz = Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Kuis Semester',
            'duration_minutes' => 20,
            'open_at' => '2026-09-15 08:00:00',
            'close_at' => '2026-09-15 10:00:00',
        ]);
        QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => '2026-09-15 08:00:00',
            'submitted_at' => '2026-09-15 08:15:00',
            'score' => 80,
        ]);

        $this->actingAs($teacher);
        Livewire::test(EvaluationManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('semesterId', (string) $semester->id)
            ->call('autoFillScores')
            ->assertSet("scores.{$student->id}", '85')
            ->set("scores.{$student->id}", '91')
            ->call('saveGrades')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'final_score' => 91,
            'predikat' => 'A',
        ]);
    }

    public function test_teacher_saves_one_class_attendance_in_one_submit(): void
    {
        [$teacher, $students, $classSubject] = $this->evaluationContext();
        $statuses = [
            $students[0]->id => 'hadir',
            $students[1]->id => 'izin',
            $students[2]->id => 'sakit',
        ];

        $this->actingAs($teacher);
        Livewire::test(EvaluationManager::class)
            ->set('tab', 'attendance')
            ->set('attendanceClassId', (string) $classSubject->class_id)
            ->set('attendanceDate', '2026-09-03')
            ->set('statuses', $statuses)
            ->call('saveAttendance')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('attendances', 3);
        $this->assertDatabaseHas('attendances', ['student_id' => $students[1]->id, 'status' => 'izin']);
    }

    public function test_student_sees_only_their_evaluation_summary(): void
    {
        [, $students, $classSubject, $semester] = $this->evaluationContext();
        Grade::query()->create([
            'student_id' => $students[0]->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'final_score' => 87,
            'predikat' => 'B',
        ]);
        Grade::query()->create([
            'student_id' => $students[1]->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'final_score' => 99,
            'predikat' => 'A',
        ]);

        $this->actingAs($students[0]);
        Livewire::test(EvaluationSummary::class)
            ->set('semesterId', (string) $semester->id)
            ->assertSee('87.00')
            ->assertDontSee('99.00');
    }

    public function test_only_admin_can_download_student_report_card_pdf(): void
    {
        [, $students, $classSubject, $semester] = $this->evaluationContext();
        $admin = User::factory()->admin()->create();
        Grade::query()->create([
            'student_id' => $students[0]->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'final_score' => 88,
            'predikat' => 'B',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.report-cards.download', [$semester, $students[0]]));
        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        $this->actingAs($students[0])
            ->get(route('admin.report-cards.download', [$semester, $students[0]]))
            ->assertForbidden();
    }

    /** @return array{User, list<User>, ClassSubject, Semester} */
    private function evaluationContext(): array
    {
        $teacher = User::factory()->teacher()->create();
        $students = User::factory()->count(3)->student(mustChangePassword: false)->create()->all();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $semester = Semester::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
        $class = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => '7A',
            'homeroom_teacher_id' => $teacher->id,
        ]);
        $class->students()->attach(collect($students)->pluck('id'));
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        return [$teacher, $students, $classSubject, $semester];
    }
}
