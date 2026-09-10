<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherAccountTest extends TestCase
{
    use RefreshDatabase;

    private function teacherWithoutEmail(): User
    {
        return User::factory()->teacher()->create([
            'name' => 'Rina Lestari',
            'email' => 'rina.lestari@guru.invalid',
            'username' => 'rina.lestari',
        ]);
    }

    public function test_teacher_can_log_in_with_username(): void
    {
        $teacher = $this->teacherWithoutEmail();

        $this->post(route('login.store'), [
            'email' => 'Rina.Lestari',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_student_cannot_use_the_staff_login_form_with_username(): void
    {
        $student = User::factory()->student()->create();

        $this->post(route('login.store'), [
            'email' => $student->username,
            'password' => 'password',
        ])->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_admin_can_view_teacher_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $withEmail = User::factory()->teacher()->create(['email' => 'hendra@sekolah.sch.id', 'nip' => '198203152008011003']);
        $withoutEmail = $this->teacherWithoutEmail();

        $this->actingAs($admin)
            ->get(route('teachers.index'))
            ->assertOk()
            ->assertSee($withEmail->email)
            ->assertSee('NIP 198203152008011003')
            ->assertSee($withoutEmail->username)
            ->assertSee('Tanpa NIP/NUPTK')
            ->assertDontSee('@guru.invalid');
    }

    public function test_admin_can_reset_teacher_password_and_the_action_is_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = $this->teacherWithoutEmail();

        $response = $this->actingAs($admin)
            ->post(route('teachers.password.reset', $teacher));

        $password = $response->getSession()->get('generated_password');

        $response->assertRedirect()->assertSessionHas('reset_user_id', $teacher->id);

        $teacher->refresh();

        $this->assertIsString($password);
        $this->assertTrue(Hash::check($password, $teacher->password));
        $this->assertTrue($teacher->must_change_password);
        $this->assertDatabaseHas('password_reset_logs', [
            'user_id' => $teacher->id,
            'reset_by_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_reset_another_teacher_password(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = $this->teacherWithoutEmail();

        $this->actingAs($teacher)
            ->post(route('teachers.password.reset', $otherTeacher))
            ->assertForbidden();
    }

    public function test_admin_accounts_cannot_be_reset_through_the_teacher_route(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('teachers.password.reset', $otherAdmin))
            ->assertNotFound();

        $this->assertDatabaseCount('password_reset_logs', 0);
    }

    public function test_teacher_route_only_accepts_teacher_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->post(route('teachers.password.reset', $student))
            ->assertNotFound();

        $this->assertDatabaseCount('password_reset_logs', 0);
    }

    public function test_admin_who_also_teaches_cannot_be_reset_by_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $teachingAdmin = User::factory()->admin()->create();
        $teachingAdmin->assignRole(Role::findOrCreate('guru', 'web'));

        $this->actingAs($admin)
            ->post(route('teachers.password.reset', $teachingAdmin))
            ->assertForbidden();

        $this->assertDatabaseCount('password_reset_logs', 0);
    }

    public function test_reset_link_is_not_sent_to_teacher_without_email(): void
    {
        Notification::fake();
        $teacher = $this->teacherWithoutEmail();

        $this->post(route('password.request'), ['email' => $teacher->email]);

        Notification::assertNotSentTo($teacher, ResetPassword::class);
    }
}
