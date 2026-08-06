<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_student_password_and_the_action_is_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();

        $response = $this->actingAs($admin)
            ->post(route('students.password.reset', $student));

        $password = $response->getSession()->get('generated_password');

        $response
            ->assertRedirect()
            ->assertSessionHas('reset_student_id', $student->id)
            ->assertSessionHas('generated_password');

        $student->refresh();

        $this->assertIsString($password);
        $this->assertTrue(Hash::check($password, $student->password));
        $this->assertTrue($student->must_change_password);
        $this->assertDatabaseHas('password_reset_logs', [
            'user_id' => $student->id,
            'reset_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_view_student_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($student->nisn);
    }

    public function test_teacher_can_reset_student_password(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($teacher)
            ->post(route('students.password.reset', $student))
            ->assertSessionHas('generated_password');
    }

    public function test_student_cannot_reset_another_student_password(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $otherStudent = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('students.password.reset', $otherStudent))
            ->assertForbidden();
    }

    public function test_student_does_not_receive_self_service_reset_email(): void
    {
        Notification::fake();
        $student = User::factory()->student()->create();

        $this->post(route('password.request'), ['email' => $student->email]);

        Notification::assertNotSentTo($student, ResetPassword::class);
    }
}
