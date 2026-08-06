<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
