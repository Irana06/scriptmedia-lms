<?php

namespace Tests\Feature;

use App\Auth\StudentAccess;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Material;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StudentAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: ClassSubject, 1: User, 2: User} */
    private function classWithTwoStudents(): array
    {
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $teacher = User::factory()->teacher()->create();
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $first = User::factory()->student(mustChangePassword: false)->create();
        $second = User::factory()->student(mustChangePassword: false)->create();
        $class->students()->attach([$first->id, $second->id]);

        return [$classSubject, $first, $second];
    }

    public function test_viewed_and_acting_student_are_the_logged_in_student(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        $this->actingAs($student);
        $access = app(StudentAccess::class);

        $this->assertTrue($access->viewedStudent()->is($student));
        $this->assertTrue($access->actingStudent()->is($student));
    }

    public function test_non_students_cannot_view_or_act_as_a_student(): void
    {
        $this->actingAs(User::factory()->teacher()->create());
        $access = app(StudentAccess::class);

        foreach (['viewedStudent', 'actingStudent'] as $method) {
            try {
                $access->{$method}();
                $this->fail("{$method}() seharusnya menolak akun non-siswa.");
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_a_student_can_only_view_their_own_data(): void
    {
        $student = User::factory()->student(mustChangePassword: false)->create();
        $classmate = User::factory()->student(mustChangePassword: false)->create();
        $teacher = User::factory()->teacher()->create();
        $access = app(StudentAccess::class);

        $this->assertTrue($access->canView($student, $student->id));
        $this->assertFalse($access->canView($student, $classmate->id));
        $this->assertFalse($access->canView($teacher, $student->id));
    }

    public function test_student_cannot_download_a_classmates_submission(): void
    {
        Storage::fake('local');
        [$classSubject, $owner, $classmate] = $this->classWithTwoStudents();

        $assignment = Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Latihan',
            'description' => 'Kerjakan lima soal.',
            'deadline' => now()->addDay(),
        ]);
        Storage::disk('local')->put('learning/submissions/jawaban.pdf', 'isi jawaban');
        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $owner->id,
            'file_path' => 'learning/submissions/jawaban.pdf',
            'submitted_at' => now(),
        ]);

        $this->actingAs($classmate)
            ->get(route('learning.files.submission', $submission))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('learning.files.submission', $submission))
            ->assertOk();
    }

    public function test_student_outside_the_class_cannot_download_its_material(): void
    {
        Storage::fake('local');
        [$classSubject, $member] = $this->classWithTwoStudents();
        $outsider = User::factory()->student(mustChangePassword: false)->create();

        $material = Material::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Ringkasan',
            'description' => 'Materi pembuka.',
            'order' => 1,
        ]);
        Storage::disk('local')->put('learning/materials/ringkasan.pdf', 'isi materi');
        $file = $material->files()->create(['type' => 'pdf', 'file_path' => 'learning/materials/ringkasan.pdf']);

        $this->actingAs($outsider)
            ->get(route('learning.files.material', $file))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('learning.files.material', $file))
            ->assertOk();
    }

    /**
     * Penjaga agar lapisan otorisasi tidak dilangkahi diam-diam. Kalau tes ini
     * gagal, pakai viewedStudentId() untuk membaca data atau actingStudentId()
     * untuk aksi — jangan identitas pengguna yang login secara langsung.
     */
    public function test_student_components_do_not_read_the_authenticated_user_directly(): void
    {
        $paths = glob(app_path('Livewire/Student/*.php')) ?: [];

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $source = (string) file_get_contents($path);

            foreach (['Auth::id()', 'Auth::user()', 'auth()->id()', 'auth()->user()'] as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    $source,
                    basename($path)." memakai {$forbidden}; gunakan viewedStudentId() atau actingStudentId().",
                );
            }
        }
    }
}
