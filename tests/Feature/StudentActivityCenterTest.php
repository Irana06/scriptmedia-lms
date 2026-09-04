<?php

namespace Tests\Feature;

use App\Livewire\Student\ActivityCenter;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentActivityCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_priorities_and_history_from_their_active_class_only(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $teacher = User::factory()->teacher()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas pekan ini',
            'deadline' => now()->addDays(3),
        ]);
        Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas terlambat',
            'deadline' => now()->subDay(),
        ]);
        $submitted = Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas sudah dikirim',
            'deadline' => now()->addDay(),
        ]);
        AssignmentSubmission::query()->create([
            'assignment_id' => $submitted->id,
            'student_id' => $student->id,
            'file_path' => 'testing/jawaban.pdf',
            'submitted_at' => now(),
        ]);
        Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Kuis aktif kelas 7A',
            'duration_minutes' => 20,
            'open_at' => now()->subHour(),
            'close_at' => now()->addDay(),
        ]);

        $otherClass = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7B']);
        $otherSubject = Subject::query()->create(['name' => 'IPA', 'code' => 'IPA']);
        $otherClassSubject = ClassSubject::query()->create([
            'class_id' => $otherClass->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $teacher->id,
        ]);
        Assignment::query()->create([
            'class_subject_id' => $otherClassSubject->id,
            'title' => 'Rahasia kelas lain',
            'deadline' => now()->addDay(),
        ]);

        $this->actingAs($student);

        Livewire::test(ActivityCenter::class)
            ->assertSee('Tugas pekan ini')
            ->assertSee('Tugas terlambat')
            ->assertSee('Kuis aktif kelas 7A')
            ->assertDontSee('Tugas sudah dikirim')
            ->assertDontSee('Rahasia kelas lain')
            ->set('tab', 'history')
            ->assertSee('Tugas sudah dikirim');
    }

    public function test_activity_center_is_available_to_students_only(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->get(route('student.activities.index'))->assertOk();
        $this->actingAs($teacher)->get(route('student.activities.index'))->assertForbidden();
    }
}
