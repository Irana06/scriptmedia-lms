<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_creates_school_profile_admin_and_active_academic_year(): void
    {
        $this->artisan('sekolah:setup', [
            '--nama' => 'SMK Digital Nusantara',
            '--npsn' => '12345678',
            '--admin-nama' => 'Kepala Sekolah',
            '--admin-email' => 'kepsek@smkdigital.sch.id',
            '--tahun-ajaran' => '2026/2027',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $this->assertSame('SMK Digital Nusantara', SchoolProfile::current()->name);
        $this->assertSame('12345678', SchoolProfile::current()->npsn);

        $admin = User::query()->where('email', 'kepsek@smkdigital.sch.id')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->must_change_password);

        $this->assertDatabaseHas('academic_years', ['year_label' => '2026/2027', 'is_active' => true]);
    }

    public function test_setup_run_twice_does_not_duplicate_admin_or_academic_year(): void
    {
        $options = [
            '--nama' => 'SMK Digital Nusantara',
            '--admin-nama' => 'Kepala Sekolah',
            '--admin-email' => 'kepsek@smkdigital.sch.id',
            '--tahun-ajaran' => '2026/2027',
            '--no-interaction' => true,
        ];

        $this->artisan('sekolah:setup', $options)->assertExitCode(0);
        $this->artisan('sekolah:setup', $options)->assertExitCode(0);

        $this->assertSame(1, User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count());
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
    }

    public function test_setup_without_admin_options_skips_admin_creation(): void
    {
        $this->artisan('sekolah:setup', [
            '--nama' => 'SMK Digital Nusantara',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, User::query()->count());
    }
}
