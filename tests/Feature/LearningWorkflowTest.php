<?php

namespace Tests\Feature;

use App\Livewire\Student\LearningCenter;
use App\Livewire\Student\QuizPlayer;
use App\Livewire\Teacher\LearningManager;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LearningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_publish_material_and_complete_assignment_workflow(): void
    {
        Storage::fake('local');
        [$teacher, $student, $classSubject] = $this->learningContext();

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('materialTitle', 'Ringkasan Aljabar')
            ->set('materialDescription', 'Pelajari sebelum pertemuan berikutnya.')
            ->set('materialType', 'pdf')
            ->set('materialUpload', UploadedFile::fake()->create('aljabar.pdf', 100, 'application/pdf'))
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->set('assignmentTitle', 'Latihan Aljabar')
            ->set('assignmentDescription', 'Kerjakan nomor 1–10.')
            ->set('assignmentDeadline', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $material = Material::query()->with('files')->firstOrFail();
        $assignment = Assignment::query()->firstOrFail();
        Storage::disk('local')->assertExists($material->files->firstOrFail()->file_path);

        $this->actingAs($student);
        Livewire::test(LearningCenter::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('submissionFile', UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'))
            ->call('submitAssignment', $assignment->id)
            ->assertHasNoErrors();

        $submission = AssignmentSubmission::query()->firstOrFail();

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->call('editGrade', $submission->id)
            ->set('gradeScore', '88')
            ->set('gradeFeedback', 'Langkah pengerjaan sudah jelas.')
            ->call('saveGrade')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assignment_grades', [
            'submission_id' => $submission->id,
            'score' => 88,
            'feedback' => 'Langkah pengerjaan sudah jelas.',
        ]);
    }

    public function test_student_cannot_submit_assignment_after_deadline(): void
    {
        Storage::fake('local');
        [, $student, $classSubject] = $this->learningContext();
        $assignment = Assignment::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Tugas ditutup',
            'deadline' => now()->subMinute(),
        ]);

        $this->actingAs($student);
        Livewire::test(LearningCenter::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('submissionFile', UploadedFile::fake()->create('terlambat.pdf', 10, 'application/pdf'))
            ->call('submitAssignment', $assignment->id)
            ->assertHasErrors(['submissionFile']);

        $this->assertDatabaseCount('assignment_submissions', 0);
    }

    public function test_five_multiple_choice_questions_are_graded_automatically(): void
    {
        [$teacher, $student, $classSubject] = $this->learningContext();

        $this->actingAs($teacher);
        $component = Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('quizTitle', 'Kuis Aljabar')
            ->set('quizDuration', '30')
            ->set('quizOpenAt', now()->subMinute()->format('Y-m-d\TH:i'))
            ->set('quizCloseAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->call('saveQuiz')
            ->assertHasNoErrors();

        $quiz = Quiz::query()->firstOrFail();
        foreach (range(1, 5) as $number) {
            $component
                ->set('questionText', "Soal nomor {$number}")
                ->set('questionType', 'mc')
                ->set('choices', ["Benar {$number}", "Salah A {$number}", "Salah B {$number}", "Salah C {$number}"])
                ->set('correctChoice', '0')
                ->call('addQuestion', $quiz->id)
                ->assertHasNoErrors();
        }

        $quiz->load('questions.choices');
        $answers = $quiz->questions->mapWithKeys(
            fn ($question): array => [$question->id => (string) $question->choices->firstWhere('is_correct', true)->id],
        )->all();

        $this->actingAs($student);
        Livewire::test(QuizPlayer::class, ['quiz' => $quiz])
            ->set('answers', $answers)
            ->call('submitQuiz')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 100,
        ]);
        $this->assertDatabaseCount('quiz_answers', 5);
    }

    public function test_teacher_can_manually_grade_an_essay_answer(): void
    {
        [$teacher, $student, $classSubject] = $this->learningContext();
        $quiz = Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Esai singkat',
            'duration_minutes' => 15,
            'open_at' => now()->subMinute(),
            'close_at' => now()->addHour(),
        ]);
        $question = $quiz->questions()->create(['question' => 'Jelaskan jawabanmu.', 'type' => 'essay']);

        $this->actingAs($student);
        Livewire::test(QuizPlayer::class, ['quiz' => $quiz])
            ->set("answers.{$question->id}", 'Penjelasan siswa.')
            ->call('submitQuiz')
            ->assertHasNoErrors();

        $answer = QuizAnswer::query()->firstOrFail();
        $this->assertNull($answer->attempt->score);

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('essayScore', '1')
            ->call('gradeEssay', $answer->id)
            ->assertHasNoErrors();

        $this->assertEquals(100, $answer->attempt->refresh()->score);
    }

    /** @return array{User, User, ClassSubject} */
    private function learningContext(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($student);
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        return [$teacher, $student, $classSubject];
    }
}
