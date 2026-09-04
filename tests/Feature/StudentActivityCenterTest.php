<?php

namespace Tests\Feature;

use App\Livewire\Student\ActivityCenter;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\AssignmentSubmission;
use App\Models\CalendarEvent;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Models\QuizAttempt;
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
        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => $submitted->id,
            'student_id' => $student->id,
            'file_path' => 'testing/jawaban.pdf',
            'submitted_at' => now(),
        ]);
        AssignmentGrade::query()->create([
            'submission_id' => $submission->id,
            'score' => 92,
            'feedback' => 'Pembahasan sudah runtut dan benar.',
        ]);
        Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Kuis aktif kelas 7A',
            'duration_minutes' => 20,
            'open_at' => now()->subHour(),
            'close_at' => now()->addDay(),
        ]);
        $quiz = Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Kuis yang sudah selesai',
            'duration_minutes' => 20,
            'open_at' => now()->subDays(2),
            'close_at' => now()->addDay(),
        ]);
        $question = $quiz->questions()->create([
            'type' => 'mc',
            'question' => 'Nilai x dari x + 2 = 5 adalah ...',
        ]);
        $choice = $question->choices()->create(['label' => '3', 'is_correct' => true]);
        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(8),
            'submitted_at' => now(),
            'score' => 100,
        ]);
        $attempt->answers()->create([
            'question_id' => $question->id,
            'answer' => (string) $choice->id,
            'score' => 1,
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
            ->assertSee('Tugas sudah dikirim')
            ->assertSee('Ketepatan waktu')
            ->assertSee('Umpan balik guru')
            ->assertSee('Pembahasan sudah runtut dan benar.')
            ->assertSee('Kuis yang sudah selesai')
            ->assertSee('Terjawab')
            ->assertSee('Jawabanmu:')
            ->assertSee('Nilai 100');
    }

    public function test_information_details_come_from_announcements_and_calendar_events(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $admin = User::factory()->admin()->create(['name' => 'Admin Sekolah']);
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);

        Announcement::query()->create([
            'title' => 'Jadwal pembagian rapor',
            'body' => 'Rapor dibagikan di ruang kelas masing-masing.',
            'target' => 'all',
            'created_by' => $admin->id,
        ]);
        CalendarEvent::query()->create([
            'title' => 'Pertemuan wali kelas',
            'date' => now()->addWeek()->toDateString(),
            'description' => 'Pertemuan dimulai pukul 09.00 di aula.',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($student);

        Livewire::test(ActivityCenter::class)
            ->set('tab', 'info')
            ->assertSee('Jadwal pembagian rapor')
            ->assertSee('Diterbitkan oleh')
            ->assertSee('Admin Sekolah')
            ->assertSee('Ditujukan kepada')
            ->assertSee('Seluruh warga sekolah')
            ->assertSee('Pertemuan wali kelas')
            ->assertSee('Tanggal pelaksanaan')
            ->assertSee('Dibuat oleh');
    }

    public function test_activity_center_is_available_to_students_only(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->get(route('student.activities.index'))->assertOk();
        $this->actingAs($teacher)->get(route('student.activities.index'))->assertForbidden();
    }
}
