<?php

namespace Tests\Feature;

use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_roles_and_admin_are_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', config('lms.default_admin.email'))->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertDatabaseHas('roles', ['name' => 'guru']);
        $this->assertDatabaseHas('roles', ['name' => 'siswa']);
    }

    public function test_admin_seeder_refuses_the_public_default_password_in_production(): void
    {
        $this->app['env'] = 'production';
        Config::set('lms.default_admin.password', 'change-this-password');

        $this->expectException(RuntimeException::class);

        // --force sendiri (bukan --no-interaction) yang melewati konfirmasi
        // bawaan Artisan "Application In Production" di seeder ini.
        $this->artisan('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);
    }

    public function test_demo_seeder_can_run_again_on_an_already_seeded_database(): void
    {
        Storage::fake('local');

        $this->seed(DemoSeeder::class);

        $counts = fn (): array => [
            'users' => User::query()->count(),
            'attendances' => Attendance::query()->count(),
            'grades' => Grade::query()->count(),
            'schedules' => Schedule::query()->count(),
            'submissions' => AssignmentSubmission::query()->count(),
        ];
        $afterFirstRun = $counts();

        // Operator menjalankan ulang seeder setelah deploy. Ini harus memperbarui
        // data demo, bukan gagal dan bukan menggandakan baris.
        $this->seed(DemoSeeder::class);

        $this->assertSame($afterFirstRun, $counts());
        $this->assertGreaterThan(0, $afterFirstRun['attendances']);
    }
}
