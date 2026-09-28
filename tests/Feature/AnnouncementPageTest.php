<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\GuardianLink;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_reads_an_announcement_of_their_class(): void
    {
        [$admin, $student, $class] = $this->context();
        $announcement = Announcement::query()->create(['title' => 'Kunjungan Museum', 'body' => "Bawa bekal.\nBerangkat 07.00.", 'target' => 'class', 'class_id' => $class->id, 'created_by' => $admin->id]);

        $this->actingAs($student)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Kunjungan Museum')
            ->assertSee('Kelas 7A')
            ->assertSee('Semua pengumuman');

        $this->assertDatabaseHas('announcement_reads', ['announcement_id' => $announcement->id, 'user_id' => $student->id]);
    }

    public function test_announcement_for_another_class_is_not_found(): void
    {
        [$admin, $student] = $this->context();
        $otherClass = SchoolClass::query()->create(['academic_year_id' => AcademicYear::query()->value('id'), 'name' => '9C']);
        $announcement = Announcement::query()->create(['title' => 'Rahasia 9C', 'body' => 'x', 'target' => 'class', 'class_id' => $otherClass->id, 'created_by' => $admin->id]);

        $this->actingAs($student)->get(route('announcements.show', $announcement))->assertNotFound();
        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_guardian_reads_announcement_for_their_childs_class_in_guardian_layout(): void
    {
        [$admin, $student, $class] = $this->context();
        $guardian = User::factory()->guardian()->create();
        GuardianLink::query()->create(['guardian_id' => $guardian->id, 'student_id' => $student->id, 'relationship' => 'ibu', 'status' => GuardianLink::APPROVED]);
        $announcement = Announcement::query()->create(['title' => 'Rapat Wali Murid', 'body' => 'Sabtu pukul 09.00.', 'target' => 'class', 'class_id' => $class->id, 'created_by' => $admin->id]);

        $this->actingAs($guardian)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Rapat Wali Murid')
            ->assertSee('Orang tua');
    }

    public function test_admin_and_teacher_see_it_in_the_staff_layout(): void
    {
        [$admin] = $this->context();
        $announcement = Announcement::query()->create(['title' => 'Libur Nasional', 'body' => 'x', 'target' => 'all', 'created_by' => $admin->id]);

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Pengumuman & Kalender', false);
    }

    /** @return array{User, User, SchoolClass} */
    private function context(): array
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);

        return [$admin, $student, $class];
    }
}
