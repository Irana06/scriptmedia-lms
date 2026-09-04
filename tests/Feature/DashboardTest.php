<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.admin'));

        $this->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Ringkasan sekolah');
    }

    public function test_teacher_is_redirected_to_the_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.guru'));
    }

    public function test_student_can_visit_the_student_dashboard(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($student)
            ->get(route('dashboard.siswa'))
            ->assertOk()
            ->assertSee('Jadwal hari ini')
            ->assertSee('Tugas mendekati deadline');
    }

    public function test_roles_cannot_access_another_roles_dashboard(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($student)
            ->get(route('dashboard.admin'))
            ->assertForbidden();
    }

    public function test_settings_use_the_navigation_shell_for_each_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Struktur Akademik')
            ->assertSee('Pengumuman & Kalender', false);

        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($student)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Navigasi bawah siswa')
            ->assertDontSee('Struktur Akademik');
    }
}
