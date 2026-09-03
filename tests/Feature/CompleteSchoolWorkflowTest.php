<?php

namespace Tests\Feature;

use App\Imports\SpreadsheetRowsImport;
use App\Livewire\Admin\AccountImport;
use App\Livewire\CommunicationManager;
use App\Livewire\Student\LearningCenter;
use App\Livewire\Teacher\EvaluationManager;
use App\Livewire\Teacher\LearningManager;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\DataImport;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CompleteSchoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_school_workflow_from_import_to_class_announcement(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $semester = Semester::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
        $schoolClass = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => '7A',
            'homeroom_teacher_id' => $teacher->id,
        ]);
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $source = Excel::raw(new class implements FromArray, WithHeadings
        {
            public function headings(): array
            {
                return ['nama', 'nisn', 'nik', 'jenis_kelamin', 'kelas/rombel'];
            }

            public function array(): array
            {
                return [['Budi Alur Lengkap', '0012345678', '3273010101010001', 'L', '7A']];
            }
        }, ExcelWriter::XLSX);

        Livewire::actingAs($admin)->test(AccountImport::class)
            ->set('type', 'siswa')
            ->set('file', UploadedFile::fake()->createWithContent('siswa.xlsx', $source))
            ->call('import')
            ->assertHasNoErrors();

        $dataImport = DataImport::query()->firstOrFail();
        $credentialReader = new SpreadsheetRowsImport;
        Excel::import($credentialReader, $dataImport->result_file_path, 'local');
        $temporaryPassword = (string) $credentialReader->rows[0]['password_awal'];
        $student = User::query()->where('nisn', '0012345678')->firstOrFail();

        $this->post(route('logout'));
        $this->post(route('siswa.login.store'), [
            'username' => $student->nisn,
            'password' => $temporaryPassword,
        ])->assertRedirect(route('siswa.password.required', absolute: false));
        $this->put(route('siswa.password.update'), [
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect(route('dashboard.siswa'));
        $this->assertFalse($student->refresh()->must_change_password);

        Livewire::actingAs($teacher)->test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('materialTitle', 'Ringkasan Aljabar')
            ->set('materialType', 'pdf')
            ->set('materialUpload', UploadedFile::fake()->create('aljabar.pdf', 50, 'application/pdf'))
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->set('assignmentTitle', 'Latihan Aljabar')
            ->set('assignmentDeadline', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $assignment = Assignment::query()->firstOrFail();
        Livewire::actingAs($student)->test(LearningCenter::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('submissionFile', UploadedFile::fake()->create('jawaban.pdf', 30, 'application/pdf'))
            ->call('submitAssignment', $assignment->id)
            ->assertHasNoErrors();

        $submission = AssignmentSubmission::query()->firstOrFail();
        Livewire::actingAs($teacher)->test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->call('editGrade', $submission->id)
            ->set('gradeScore', '88')
            ->set('gradeFeedback', 'Pengerjaan lengkap.')
            ->call('saveGrade')
            ->assertHasNoErrors();

        Livewire::actingAs($teacher)->test(EvaluationManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('semesterId', (string) $semester->id)
            ->set("scores.{$student->id}", '88')
            ->call('saveGrades')
            ->assertHasNoErrors()
            ->set('tab', 'attendance')
            ->set('attendanceClassId', (string) $schoolClass->id)
            ->set('attendanceDate', now()->format('Y-m-d'))
            ->set("statuses.{$student->id}", 'hadir')
            ->call('saveAttendance')
            ->assertHasNoErrors();

        $report = $this->actingAs($admin)->get(route('admin.report-cards.download', [$semester, $student]));
        $report->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $report->getContent());

        Livewire::actingAs($teacher)->test(CommunicationManager::class)
            ->set('announcementTitle', 'Informasi kelas 7A')
            ->set('announcementBody', 'Bawa buku latihan besok.')
            ->set('announcementTarget', 'class')
            ->set('announcementClassId', (string) $schoolClass->id)
            ->call('saveAnnouncement')
            ->assertHasNoErrors();

        $this->actingAs($student)->get(route('dashboard.siswa'))
            ->assertOk()
            ->assertSee('Informasi kelas 7A')
            ->assertSee('88');
    }

    public function test_student_cannot_submit_another_classes_assignment(): void
    {
        Storage::fake('local');
        [, $ownerClassSubject] = $this->classSubject('7A', 'MAT');
        [, $otherClassSubject] = $this->classSubject('7B', 'IPA');
        $student = User::factory()->student(mustChangePassword: false)->create();
        $otherClassSubject->schoolClass->students()->attach($student);
        $assignment = Assignment::query()->create([
            'class_subject_id' => $ownerClassSubject->id,
            'title' => 'Tugas kelas 7A',
            'deadline' => now()->addDay(),
        ]);

        try {
            Livewire::actingAs($student)->test(LearningCenter::class)
                ->set('submissionFile', UploadedFile::fake()->create('jawaban.pdf', 10, 'application/pdf'))
                ->call('submitAssignment', $assignment->id);
            $this->fail('Siswa dapat mengumpulkan tugas milik kelas lain.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseMissing('assignment_submissions', [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ]);
        }
    }

    public function test_teacher_cannot_grade_a_submission_from_another_teacher(): void
    {
        Storage::fake('local');
        [, $classSubject] = $this->classSubject('7A', 'MAT');
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $classSubject->schoolClass->students()->attach($student);
        $assignment = Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas guru pertama',
            'deadline' => now()->addDay(),
        ]);
        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'file_path' => 'testing/jawaban.pdf',
            'submitted_at' => now(),
        ]);

        try {
            Livewire::actingAs($otherTeacher)->test(LearningManager::class)
                ->call('editGrade', $submission->id);
            $this->fail('Guru dapat menilai tugas dari kelas yang tidak diampunya.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseMissing('assignment_grades', ['submission_id' => $submission->id]);
        }
    }

    /** @return array{User, ClassSubject} */
    private function classSubject(string $className, string $subjectCode): array
    {
        $teacher = User::factory()->teacher()->create();
        $year = AcademicYear::query()->create([
            'year_label' => "2026/2027-{$className}",
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => $className,
        ]);
        $subject = Subject::query()->create([
            'name' => "Mapel {$subjectCode}",
            'code' => $subjectCode,
        ]);

        return [$teacher, ClassSubject::query()->create([
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ])];
    }
}
