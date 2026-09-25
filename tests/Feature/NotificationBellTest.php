<?php

namespace Tests\Feature;

use App\Livewire\Guardian\GuardianHome;
use App\Livewire\NotificationBell;
use App\Livewire\Teacher\LearningManager;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\GuardianLink;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_bell_shows_no_badge_without_anything_new(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(NotificationBell::class)
            ->assertDontSeeHtml('data-test="notification-count"')
            ->assertSee('Belum ada notifikasi.');
    }

    public function test_grading_notifies_the_student_and_approved_guardian_only(): void
    {
        [$teacher, $student, $classSubject] = $this->context();
        $guardian = User::factory()->guardian()->create();
        $pendingGuardian = User::factory()->guardian()->create();
        GuardianLink::query()->create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'relationship' => 'ibu', 'status' => GuardianLink::APPROVED]);
        GuardianLink::query()->create(['guardian_id' => $pendingGuardian->id, 'student_id' => $student->id, 'relationship' => 'ayah', 'status' => GuardianLink::PENDING]);
        $assignment = Assignment::query()->create(['class_subject_id' => $classSubject->id, 'title' => 'Latihan Pecahan', 'deadline' => now()->addDay()]);
        $submission = AssignmentSubmission::query()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'file_path' => 'x.pdf', 'submitted_at' => now()]);

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->call('editGrade', $submission->id)
            ->set('gradeScore', '87.5')
            ->call('saveGrade')
            ->assertHasNoErrors();

        $this->assertSame(1, $student->unreadNotifications()->count());
        $this->assertStringContainsString('nilai 87,5', (string) $student->unreadNotifications()->first()?->data['body']);
        $this->assertSame(1, $guardian->unreadNotifications()->count());
        $this->assertSame(0, $pendingGuardian->notifications()->count());
    }

    public function test_new_assignment_notifies_students_and_opening_it_marks_read(): void
    {
        [$teacher, $student, $classSubject] = $this->context();

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('assignmentTitle', 'Esai Sejarah')
            ->set('assignmentDeadline', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $notification = $student->unreadNotifications()->firstOrFail();

        $this->actingAs($student);
        Livewire::test(NotificationBell::class)
            ->assertSeeHtml('data-test="notification-count"')
            ->assertSee('Esai Sejarah')
            ->call('open', $notification->id)
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()?->read_at);
    }

    public function test_new_announcements_count_until_seen_and_respect_class_targeting(): void
    {
        [$teacher, $student, $classSubject] = $this->context();
        $student->forceFill(['created_at' => now()->subWeek()])->save();
        $otherClass = SchoolClass::query()->create(['academic_year_id' => $classSubject->schoolClass->academic_year_id, 'name' => '8B']);
        Announcement::query()->create(['title' => 'Libur', 'body' => 'x', 'target' => 'all', 'created_by' => $teacher->id]);
        Announcement::query()->create(['title' => 'Kelas lain', 'body' => 'x', 'target' => 'class', 'class_id' => $otherClass->id, 'created_by' => $teacher->id]);

        $this->actingAs($student);
        Livewire::test(NotificationBell::class)
            ->assertSee('1 pengumuman baru')
            ->call('markAllRead')
            ->assertDontSee('pengumuman baru');
    }

    public function test_guardian_link_request_notifies_admins(): void
    {
        $admin = User::factory()->admin()->create();
        [, $student] = $this->context();
        $guardian = User::factory()->guardian()->create();

        $this->actingAs($guardian);
        Livewire::test(GuardianHome::class)
            ->set('studentIdentifier', (string) $student->nisn)
            ->set('studentName', $student->name)
            ->set('relationship', 'ayah')
            ->call('requestLink')
            ->assertHasNoErrors();

        $this->assertSame(1, $admin->unreadNotifications()->count());
    }

    /** @return array{User, User, ClassSubject} */
    private function context(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        return [$teacher, $student, $classSubject];
    }
}
