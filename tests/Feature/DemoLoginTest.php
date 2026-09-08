<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_are_hidden_and_unavailable_when_demo_mode_is_disabled(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Coba akun demo');

        $this->post(route('demo.login', 'admin'))->assertNotFound();
    }

    public function test_demo_mode_shows_accounts_and_logs_in_the_selected_demo_user(): void
    {
        config()->set('app.demo_mode', true);
        $admin = User::factory()->create([
            'email' => 'admin.demo@example.com',
            'name' => 'Admin Demo',
        ]);
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Coba akun demo')
            ->assertSee('Masuk Admin')
            ->assertSee('Masuk Guru')
            ->assertSee('Masuk Siswa');

        $this->post(route('demo.login', 'admin'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }
}
