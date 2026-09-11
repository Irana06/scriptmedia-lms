<?php

namespace Tests\Feature;

use App\Actions\ResetAccountPassword;
use App\Auth\StudentAccess;
use App\Livewire\Admin\GuardianManager;
use App\Livewire\Guardian\GuardianHome;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\GuardianLink;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolDay;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GuardianModuleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ClassSubject, 2: Semester} */
    private function studentInClass(string $nisn = '0012345678', string $name = 'Budi Santoso'): array
    {
        $year = AcademicYear::query()->firstOrCreate(['year_label' => '2026/2027'], ['is_active' => true]);
        $semester = Semester::query()->firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Ganjil'],
            ['start_date' => today()->subMonths(2)->toDateString(), 'end_date' => today()->addMonths(3)->toDateString()],
        );
        $class = SchoolClass::query()->firstOrCreate(['academic_year_id' => $year->id, 'name' => '7A']);
        $subject = Subject::query()->create(['name' => 'Matematika '.$nisn, 'code' => 'MAT-'.$nisn]);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => User::factory()->teacher()->create()->id,
        ]);
        $student = User::factory()->student(mustChangePassword: false)->create([
            'name' => $name,
            'nisn' => $nisn,
            'username' => $nisn,
            'email' => $nisn.'@students.invalid',
        ]);
        $class->students()->attach($student->id);

        return [$student, $classSubject, $semester];
    }

    private function guardianOf(User $student, string $status = GuardianLink::APPROVED): User
    {
        $guardian = User::factory()->guardian()->create();

        GuardianLink::query()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'ibu',
            'status' => $status,
        ]);

        return $guardian;
    }

    public function test_parent_can_register_and_only_ever_receives_the_parent_role(): void
    {
        $this->post(route('ortu.register.store'), [
            'name' => 'Sri Wahyuni',
            'email' => 'Sri@Contoh.id',
            'phone' => '0812 3456 7890',
            'password' => 'PasswordOrtu123',
            'password_confirmation' => 'PasswordOrtu123',
            'role' => 'admin',
        ])->assertRedirect(route('dashboard.ortu'));

        $guardian = User::query()->where('email', 'sri@contoh.id')->firstOrFail();

        $this->assertAuthenticatedAs($guardian);
        $this->assertSame(['ortu'], $guardian->getRoleNames()->all());
        $this->assertSame(0, $guardian->guardianLinks()->count());
    }

    public function test_parent_can_log_in_from_the_parent_page(): void
    {
        $guardian = User::factory()->guardian()->create();

        $this->get(route('ortu.login'))->assertOk()->assertSee('Portal orang tua');

        $this->post(route('login.store'), ['email' => $guardian->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard.ortu', absolute: false));

        $this->assertAuthenticatedAs($guardian);
    }

    public function test_new_parent_request_waits_for_approval_and_grants_no_access(): void
    {
        [$student] = $this->studentInClass();
        $guardian = User::factory()->guardian()->create();

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->set('studentIdentifier', '0012345678')
            ->set('studentName', '  budi   SANTOSO ')
            ->set('relationship', 'ibu')
            ->call('requestLink')
            ->assertHasNoErrors()
            ->assertSee('Menunggu persetujuan')
            ->assertViewHas('overview', fn ($overview): bool => $overview === null);

        $this->assertDatabaseHas('guardian_student', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'status' => GuardianLink::PENDING,
        ]);
        $this->assertFalse(app(StudentAccess::class)->canView($guardian, $student->id));
    }

    public function test_link_request_gives_the_same_answer_for_unknown_students_and_wrong_names(): void
    {
        $this->studentInClass();
        $guardian = User::factory()->guardian()->create();

        $wrongName = Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->set('studentIdentifier', '0012345678')
            ->set('studentName', 'Andi Pratama')
            ->set('relationship', 'ayah')
            ->call('requestLink')
            ->assertHasErrors('studentIdentifier');

        $unknownStudent = Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->set('studentIdentifier', '0099999999')
            ->set('studentName', 'Budi Santoso')
            ->set('relationship', 'ayah')
            ->call('requestLink')
            ->assertHasErrors('studentIdentifier');

        $this->assertSame(
            $wrongName->errors()->first('studentIdentifier'),
            $unknownStudent->errors()->first('studentIdentifier'),
        );
        $this->assertDatabaseCount('guardian_student', 0);
    }

    public function test_approved_parent_sees_schedule_attendance_and_tasks_but_no_grades(): void
    {
        [$student, $classSubject, $semester] = $this->studentInClass();
        $guardian = $this->guardianOf($student);

        Schedule::query()->create([
            'class_subject_id' => $classSubject->id,
            'day' => SchoolDay::today(),
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ]);
        Attendance::query()->create([
            'class_id' => $classSubject->class_id,
            'student_id' => $student->id,
            'date' => today(),
            'status' => 'izin',
        ]);
        Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Latihan Pecahan',
            'description' => 'Lima soal.',
            'deadline' => now()->addDays(3),
        ]);
        Grade::query()->create([
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'final_score' => 91.25,
            'predikat' => 'A',
        ]);

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->assertSee($student->name)
            ->assertSee('Matematika 0012345678')
            ->assertSee('Latihan Pecahan')
            ->assertSee('Belum dikumpulkan')
            ->assertDontSee('91.25')
            ->assertDontSee('91,25')
            ->assertViewHas('overview', fn (array $overview): bool => $overview['attendanceCounts']['izin'] === 1
                && ! array_key_exists('grades', $overview));
    }

    public function test_parent_cannot_select_a_child_they_are_not_linked_to(): void
    {
        [$child] = $this->studentInClass();
        [$otherChild] = $this->studentInClass('0012345679', 'Siti Aisyah');
        $guardian = $this->guardianOf($child);

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->call('selectChild', $otherChild->id)
            ->assertForbidden();

        $this->assertFalse(app(StudentAccess::class)->canView($guardian, $otherChild->id));
    }

    public function test_only_approved_links_grant_access(): void
    {
        [$student] = $this->studentInClass();
        $access = app(StudentAccess::class);

        $this->assertFalse($access->canView($this->guardianOf($student, GuardianLink::PENDING), $student->id));
        $this->assertFalse($access->canView($this->guardianOf($student, GuardianLink::REJECTED), $student->id));
        $this->assertTrue($access->canView($this->guardianOf($student), $student->id));
    }

    public function test_parent_can_never_act_as_or_enter_the_student_area(): void
    {
        [$student] = $this->studentInClass();
        $guardian = $this->guardianOf($student);

        $this->actingAs($guardian)->get(route('student.learning.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('dashboard.siswa'))->assertForbidden();

        try {
            app(StudentAccess::class)->actingStudent();
            $this->fail('Orang tua tidak boleh bertindak sebagai siswa.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_admin_can_approve_and_reject_pending_requests(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();
        $mother = $this->guardianOf($student, GuardianLink::PENDING);
        $stranger = $this->guardianOf($student, GuardianLink::PENDING);
        $motherLink = GuardianLink::query()->where('guardian_id', $mother->id)->firstOrFail();
        $strangerLink = GuardianLink::query()->where('guardian_id', $stranger->id)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(GuardianManager::class)
            ->assertSee($mother->name)
            ->call('approve', $motherLink->id)
            ->call('reject', $strangerLink->id);

        $this->assertSame(GuardianLink::APPROVED, $motherLink->fresh()?->status);
        $this->assertSame($admin->id, $motherLink->fresh()?->reviewed_by);
        $this->assertSame(GuardianLink::REJECTED, $strangerLink->fresh()?->status);
        $this->assertTrue(app(StudentAccess::class)->canView($mother, $student->id));
        $this->assertFalse(app(StudentAccess::class)->canView($stranger, $student->id));
    }

    public function test_admin_can_add_a_parent_without_email_who_is_linked_immediately(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GuardianManager::class)
            ->set('name', 'Pak Joko')
            ->set('phone', '081234567890')
            ->set('studentIdentifier', '0012345678')
            ->set('relationship', 'ayah')
            ->call('createGuardian')
            ->assertHasNoErrors()
            ->assertSet('createdCredentials.login', 'pak.joko');

        $guardian = User::query()->where('username', 'pak.joko')->firstOrFail();

        $this->assertSame('pak.joko@ortu.invalid', $guardian->email);
        $this->assertTrue($guardian->hasRole('ortu'));
        $this->assertTrue($guardian->must_change_password);
        $this->assertTrue(app(StudentAccess::class)->canView($guardian, $student->id));
    }

    public function test_revoking_a_link_removes_access_immediately(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();
        $guardian = $this->guardianOf($student);
        $link = GuardianLink::query()->where('guardian_id', $guardian->id)->firstOrFail();

        Livewire::actingAs($admin)->test(GuardianManager::class)->call('revoke', $link->id);

        $this->assertDatabaseMissing('guardian_student', ['id' => $link->id]);
        $this->assertFalse(app(StudentAccess::class)->canView($guardian, $student->id));
    }

    public function test_only_admins_can_manage_parent_accounts(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.guardians.index'))->assertForbidden();
        Livewire::actingAs($teacher)->test(GuardianManager::class)->assertForbidden();
    }

    public function test_parent_forced_to_change_password_returns_to_the_parent_dashboard(): void
    {
        $guardian = User::factory()->guardian()->create(['must_change_password' => true]);

        $this->actingAs($guardian)
            ->put(route('siswa.password.update'), [
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBaru123',
            ])
            ->assertRedirect(route('dashboard.ortu'));
    }

    public function test_admin_dashboard_announces_pending_parent_requests_only_when_there_are_some(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertDontSee('permintaan orang tua menunggu persetujuan');

        $this->guardianOf($student, GuardianLink::PENDING);
        $this->guardianOf($student, GuardianLink::PENDING);
        $this->guardianOf($student);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('2 permintaan orang tua menunggu persetujuan')
            ->assertSee(route('admin.guardians.index'), false);
    }

    public function test_admin_can_reset_the_password_of_a_parent_without_email(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();
        $guardian = $this->guardianOf($student);
        $guardian->forceFill([
            'username' => 'pak.joko',
            'email' => 'pak.joko@ortu.invalid',
            'must_change_password' => false,
        ])->save();

        $component = Livewire::actingAs($admin)
            ->test(GuardianManager::class)
            ->assertSee('Reset password')
            ->call('resetPassword', $guardian->id)
            ->assertHasNoErrors()
            ->assertSet('createdCredentials.login', 'pak.joko');

        $password = $component->get('createdCredentials')['password'];
        $guardian->refresh();

        $this->assertTrue(Hash::check($password, $guardian->password));
        $this->assertTrue($guardian->must_change_password);
        $this->assertDatabaseHas('password_reset_logs', [
            'user_id' => $guardian->id,
            'reset_by_user_id' => $admin->id,
        ]);
    }

    public function test_teachers_cannot_reset_parent_passwords(): void
    {
        [$student] = $this->studentInClass();
        $teacher = User::factory()->teacher()->create();
        $guardian = $this->guardianOf($student);

        $this->expectException(AuthorizationException::class);

        app(ResetAccountPassword::class)->handle($guardian, $teacher);
    }

    public function test_parent_password_reset_only_accepts_parent_accounts(): void
    {
        [$student] = $this->studentInClass();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(GuardianManager::class)
            ->call('resetPassword', $student->id)
            ->assertNotFound();

        $this->assertDatabaseCount('password_reset_logs', 0);
    }
}
