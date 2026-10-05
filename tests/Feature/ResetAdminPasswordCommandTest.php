<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetAdminPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_resets_admin_password_and_forces_change(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'kepsek@sekolah.sch.id', 'must_change_password' => false]);
        $oldHash = $admin->password;

        $this->artisan('sekolah:admin-password', ['login' => 'kepsek@sekolah.sch.id'])->assertExitCode(0);

        $admin->refresh();
        $this->assertNotSame($oldHash, $admin->password);
        $this->assertTrue($admin->must_change_password);
    }

    public function test_refuses_non_admin_accounts(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'guru@sekolah.sch.id']);

        $this->artisan('sekolah:admin-password', ['login' => 'guru@sekolah.sch.id'])->assertExitCode(1);

        $this->assertTrue(Hash::check('password', $teacher->fresh()?->password ?? ''));
    }

    public function test_without_argument_lists_admins(): void
    {
        User::factory()->admin()->create(['name' => 'Admin Sekolah']);

        $this->artisan('sekolah:admin-password')->expectsOutputToContain('Admin Sekolah')->assertExitCode(0);
    }
}
