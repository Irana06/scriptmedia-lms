<?php

namespace Tests\Feature;

use App\Livewire\Admin\AcademicSetup;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_academic_setup(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(route('admin.academic.index'))
            ->assertOk()
            ->assertSee('Struktur tahun ajaran');

        $this->actingAs($teacher)
            ->get(route('admin.academic.index'))
            ->assertForbidden();
    }

    public function test_admin_can_build_a_complete_academic_structure(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($admin);

        Livewire::test(AcademicSetup::class)
            ->set('yearLabel', '2026/2027')
            ->set('isActive', true)
            ->call('saveAcademicYear')
            ->assertHasNoErrors();

        $year = AcademicYear::query()->firstOrFail();

        Livewire::test(AcademicSetup::class)
            ->set('semesterAcademicYearId', (string) $year->id)
            ->set('semesterName', 'Ganjil')
            ->set('semesterStartDate', '2026-07-13')
            ->set('semesterEndDate', '2026-12-18')
            ->call('saveSemester')
            ->assertHasNoErrors()
            ->set('classAcademicYearId', (string) $year->id)
            ->set('className', '7A')
            ->set('homeroomTeacherId', (string) $teacher->id)
            ->call('saveSchoolClass')
            ->assertHasNoErrors()
            ->set('subjectName', 'Matematika')
            ->set('subjectCode', 'MAT')
            ->call('saveSubject')
            ->assertHasNoErrors();

        $class = SchoolClass::query()->firstOrFail();
        $subject = Subject::query()->firstOrFail();

        Livewire::test(AcademicSetup::class)
            ->set('assignmentClassId', (string) $class->id)
            ->set('assignmentSubjectId', (string) $subject->id)
            ->set('assignmentTeacherId', (string) $teacher->id)
            ->call('saveAssignment')
            ->assertHasNoErrors()
            ->set('studentClassId', (string) $class->id)
            ->set('selectedStudentId', (string) $student->id)
            ->call('addStudentToClass')
            ->assertHasNoErrors();

        $assignment = ClassSubject::query()->firstOrFail();

        Livewire::test(AcademicSetup::class)
            ->set('scheduleClassId', (string) $class->id)
            ->set('scheduleClassSubjectId', (string) $assignment->id)
            ->set('scheduleDay', 'Senin')
            ->set('scheduleStartTime', '07:00')
            ->set('scheduleEndTime', '07:45')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('semesters', ['academic_year_id' => $year->id, 'name' => 'Ganjil']);
        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('schedules', ['class_subject_id' => $assignment->id, 'day' => 'Senin']);
    }

    public function test_same_teacher_cannot_have_overlapping_schedules(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $firstClass = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $secondClass = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7B']);
        $math = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $science = Subject::query()->create(['name' => 'IPA', 'code' => 'IPA']);
        $firstAssignment = ClassSubject::query()->create(['class_id' => $firstClass->id, 'subject_id' => $math->id, 'teacher_id' => $teacher->id]);
        $secondAssignment = ClassSubject::query()->create(['class_id' => $secondClass->id, 'subject_id' => $science->id, 'teacher_id' => $teacher->id]);

        Schedule::query()->create([
            'class_subject_id' => $firstAssignment->id,
            'day' => 'Senin',
            'start_time' => '07:00',
            'end_time' => '08:00',
        ]);

        $this->actingAs($admin);

        Livewire::test(AcademicSetup::class)
            ->set('scheduleClassId', (string) $secondClass->id)
            ->set('scheduleClassSubjectId', (string) $secondAssignment->id)
            ->set('scheduleDay', 'Senin')
            ->set('scheduleStartTime', '07:30')
            ->set('scheduleEndTime', '08:30')
            ->call('saveSchedule')
            ->assertHasErrors(['scheduleEndTime' => 'Guru tersebut sudah memiliki jadwal lain pada waktu yang bertabrakan.']);

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_student_cannot_be_placed_in_two_classes_in_same_academic_year(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $firstClass = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $secondClass = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7B']);
        $firstClass->students()->attach($student);

        $this->actingAs($admin);

        Livewire::test(AcademicSetup::class)
            ->set('studentClassId', (string) $secondClass->id)
            ->set('selectedStudentId', (string) $student->id)
            ->call('addStudentToClass')
            ->assertHasErrors(['selectedStudentId']);

        $this->assertDatabaseCount('class_students', 1);
    }
}
