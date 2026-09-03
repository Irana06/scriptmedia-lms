<?php

namespace Tests\Feature;

use App\Livewire\CommunicationManager;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunicationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_only_see_schoolwide_and_their_class_announcements(): void
    {
        $admin = User::factory()->admin()->create();
        $firstStudent = User::factory()->student(mustChangePassword: false)->create();
        $secondStudent = User::factory()->student(mustChangePassword: false)->create();
        [$firstClass, $secondClass] = $this->classes();
        $firstClass->students()->attach($firstStudent);
        $secondClass->students()->attach($secondStudent);

        Announcement::query()->create([
            'title' => 'Untuk seluruh siswa',
            'body' => 'Informasi sekolah.',
            'target' => 'all',
            'created_by' => $admin->id,
        ]);
        Announcement::query()->create([
            'title' => 'Khusus kelas pertama',
            'body' => 'Informasi kelas.',
            'target' => 'class',
            'class_id' => $firstClass->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($firstStudent)->get(route('dashboard.siswa'))
            ->assertOk()
            ->assertSee('Untuk seluruh siswa')
            ->assertSee('Khusus kelas pertama');

        $this->actingAs($secondStudent)->get(route('dashboard.siswa'))
            ->assertOk()
            ->assertSee('Untuk seluruh siswa')
            ->assertDontSee('Khusus kelas pertama');
    }

    public function test_teacher_can_publish_for_an_assigned_class_and_create_an_event(): void
    {
        $teacher = User::factory()->teacher()->create();
        [$schoolClass] = $this->classes();
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        ClassSubject::query()->create([
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        Livewire::actingAs($teacher)->test(CommunicationManager::class)
            ->set('announcementTitle', 'Persiapan ulangan')
            ->set('announcementBody', 'Pelajari bab satu sampai tiga.')
            ->set('announcementTarget', 'class')
            ->set('announcementClassId', (string) $schoolClass->id)
            ->call('saveAnnouncement')
            ->assertHasNoErrors()
            ->set('eventTitle', 'Ulangan Matematika')
            ->set('eventDate', now()->addWeek()->format('Y-m-d'))
            ->set('eventDescription', 'Ruang kelas masing-masing.')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Persiapan ulangan',
            'class_id' => $schoolClass->id,
            'created_by' => $teacher->id,
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Ulangan Matematika',
            'created_by' => $teacher->id,
        ]);
    }

    public function test_teacher_cannot_publish_to_an_unassigned_class(): void
    {
        $teacher = User::factory()->teacher()->create();
        [, $unassignedClass] = $this->classes();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($teacher)->test(CommunicationManager::class)
            ->set('announcementTitle', 'Tidak diizinkan')
            ->set('announcementBody', 'Bukan kelas yang diampu.')
            ->set('announcementTarget', 'class')
            ->set('announcementClassId', (string) $unassignedClass->id)
            ->call('saveAnnouncement');
    }

    public function test_only_staff_can_open_the_communication_workspace(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($student)->get(route('communications.index'))->assertForbidden();
    }

    /** @return array{SchoolClass, SchoolClass} */
    private function classes(): array
    {
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);

        return [
            SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => 'X IPA 1']),
            SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => 'X IPA 2']),
        ];
    }
}
