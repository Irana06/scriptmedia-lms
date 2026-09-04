<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_screen_can_be_rendered(): void
    {
        $this->get(route('siswa.login'))
            ->assertOk()
            ->assertSee('RuangKelas')
            ->assertSee('Portal siswa')
            ->assertSee('Buka portal staf');
    }

    public function test_student_logs_in_with_nisn_and_must_change_initial_password(): void
    {
        $student = User::factory()->student()->create();

        $this->post(route('siswa.login.store'), [
            'username' => $student->username,
            'password' => 'password',
        ])->assertRedirect(route('siswa.password.required', absolute: false));

        $this->assertAuthenticatedAs($student);

        $this->get(route('dashboard.siswa'))
            ->assertRedirect(route('siswa.password.required'));
    }

    public function test_student_can_change_initial_password_and_continue_to_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->put(route('siswa.password.update'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.siswa'));

        $student->refresh();

        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('new-secure-password', $student->password));
    }

    public function test_student_without_password_change_requirement_goes_to_dashboard(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->post(route('siswa.login.store'), [
            'username' => $student->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.siswa', absolute: false));
    }

    public function test_staff_cannot_use_student_login(): void
    {
        $teacher = User::factory()->teacher()->create(['username' => '1234567890']);

        $this->post(route('siswa.login.store'), [
            'username' => $teacher->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
