<?php

namespace Tests\Feature;

use App\Livewire\Admin\GradeHistory;
use App\Livewire\Teacher\EvaluationManager;
use App\Livewire\Teacher\LearningManager;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\GradeAudit;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GradeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_grade_changes_are_recorded_only_when_the_score_changes(): void
    {
        [$teacher, $student, $classSubject, $semester] = $this->context();

        $this->actingAs($teacher);
        $component = Livewire::test(EvaluationManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('semesterId', (string) $semester->id)
            ->set("scores.{$student->id}", '80')
            ->call('saveGrades');
        $component->call('saveGrades');
        $component->set("scores.{$student->id}", '85')->call('saveGrades');

        $audits = GradeAudit::query()->orderBy('id')->get();
        $this->assertCount(2, $audits);
        $this->assertNull($audits[0]->old_score);
        $this->assertSame(80.0, (float) $audits[0]->new_score);
        $this->assertSame(80.0, (float) $audits[1]->old_score);
        $this->assertSame(85.0, (float) $audits[1]->new_score);
        $this->assertSame($teacher->name, $audits[1]->changed_by_name);
    }

    public function test_assignment_grade_is_recorded_and_visible_to_admin_only(): void
    {
        [$teacher, $student, $classSubject] = $this->context();
        $assignment = Assignment::query()->create(['class_subject_id' => $classSubject->id, 'title' => 'Laporan Praktikum', 'deadline' => now()->addDay()]);
        $submission = AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'file_path' => 'x.pdf', 'submitted_at' => now()]);

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->call('editGrade', $submission->id)
            ->set('gradeScore', '70')
            ->call('saveGrade');

        $this->assertDatabaseHas('grade_audits', ['kind' => 'assignment', 'item' => 'Laporan Praktikum', 'student_id' => $student->id]);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(GradeHistory::class)
            ->assertSee('Laporan Praktikum')
            ->set('search', 'tidak-ada-yang-cocok')
            ->assertDontSee('Laporan Praktikum');

        $this->actingAs($teacher)->get(route('admin.grade-history.index'))->assertForbidden();
    }

    /** @return array{User, User, ClassSubject, Semester} */
    private function context(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);
        $subject = Subject::query()->create(['name' => 'IPA', 'code' => 'IPA']);
        $classSubject = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        return [$teacher, $student, $classSubject, $semester];
    }
}
