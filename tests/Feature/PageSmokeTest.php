<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Setiap halaman utama dirender dengan data demo lengkap, untuk tiap peran.
 * Menangkap error tampilan yang tidak tersentuh tes alur lain.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs(User::query()->where('email', 'admin.demo@example.com')->firstOrFail());

        foreach ([
            'dashboard.admin', 'admin.academic.index', 'admin.imports.index', 'admin.report-cards.index',
            'admin.guardians.index', 'admin.school.index', 'admin.promotion.index', 'admin.monitoring.index',
            'admin.grade-history.index', 'admin.backups.index', 'teachers.index', 'students.index', 'communications.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }

        foreach (['years', 'classes', 'subjects', 'assignments', 'students', 'schedules', 'weights'] as $tab) {
            $this->get(route('admin.academic.index', ['tab' => $tab]))->assertOk();
        }
    }

    public function test_teacher_pages_render(): void
    {
        $this->actingAs(User::query()->where('email', 'guru.demo@example.com')->firstOrFail());

        $this->get(route('dashboard.guru'))->assertOk();
        foreach (['materials', 'assignments', 'quizzes'] as $tab) {
            $this->get(route('teacher.learning.index', ['tab' => $tab]))->assertOk();
        }
        foreach (['grades', 'attendance'] as $tab) {
            $this->get(route('teacher.evaluation.index', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('admin.report-cards.index'))->assertOk();
    }

    public function test_student_pages_render(): void
    {
        $this->actingAs(User::query()->where('email', '0099000001@students.invalid')->firstOrFail());

        $this->get(route('dashboard.siswa'))->assertOk();
        foreach (['materials', 'assignments', 'quizzes'] as $tab) {
            $this->get(route('student.learning.index', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('student.activities.index'))->assertOk();
        $this->get(route('student.evaluation.index'))->assertOk();
    }

    public function test_guardian_home_renders(): void
    {
        $this->actingAs(User::query()->where('email', 'ortu.demo@example.com')->firstOrFail())
            ->get(route('dashboard.ortu'))
            ->assertOk();
    }
}
