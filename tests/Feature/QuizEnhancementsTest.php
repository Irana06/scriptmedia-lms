<?php

namespace Tests\Feature;

use App\Exports\QuizQuestionTemplateExport;
use App\Livewire\Student\QuizPlayer;
use App\Livewire\Teacher\LearningManager;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class QuizEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shuffled_order_is_stable_per_student_and_differs_between_students(): void
    {
        [, $students, $classSubject] = $this->context(studentCount: 6);
        $quiz = $this->quizWithQuestions($classSubject, shuffle: true, questionCount: 10);

        $orders = [];
        foreach ($students as $student) {
            $this->actingAs($student);
            $first = Livewire::test(QuizPlayer::class, ['quiz' => $quiz])->viewData('questions')->pluck('id')->all();
            $again = Livewire::test(QuizPlayer::class, ['quiz' => $quiz])->viewData('questions')->pluck('id')->all();

            $this->assertSame($first, $again);
            $this->assertEqualsCanonicalizing($quiz->questions()->pluck('id')->all(), $first);
            $orders[] = implode(',', $first);
        }

        $this->assertGreaterThan(1, count(array_unique($orders)));
    }

    public function test_unshuffled_quiz_keeps_teacher_order(): void
    {
        [, $students, $classSubject] = $this->context();
        $quiz = $this->quizWithQuestions($classSubject, shuffle: false, questionCount: 5);

        $this->actingAs($students[0]);
        $order = Livewire::test(QuizPlayer::class, ['quiz' => $quiz])->viewData('questions')->pluck('id')->all();

        $this->assertSame($quiz->questions()->orderBy('id')->pluck('id')->all(), $order);
    }

    public function test_teacher_adds_question_with_image(): void
    {
        Storage::fake('public');
        [$teacher, , $classSubject] = $this->context();
        $quiz = $this->quizWithQuestions($classSubject, shuffle: true, questionCount: 0);

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('questionText', 'Bangun apakah ini?')
            ->set('questionType', 'essay')
            ->set('questionImage', UploadedFile::fake()->image('segitiga.png'))
            ->call('addQuestion', $quiz->id)
            ->assertHasNoErrors();

        $question = $quiz->questions()->firstOrFail();
        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists((string) $question->image_path);
    }

    public function test_teacher_imports_questions_from_excel_and_bad_rows_are_reported(): void
    {
        [$teacher, , $classSubject] = $this->context();
        $quiz = $this->quizWithQuestions($classSubject, shuffle: true, questionCount: 0);
        $rows = (new QuizQuestionTemplateExport)->array();
        $rows[] = ['Soal tanpa kunci', 'PG', 'a', 'b', 'c', 'd', 'E'];
        $content = Excel::raw(new class($rows) implements FromArray, WithHeadings
        {
            /** @param array<int, list<string>> $rows */
            public function __construct(private array $rows) {}

            /** @return array<int, list<string>> */
            public function array(): array
            {
                return $this->rows;
            }

            /** @return list<string> */
            public function headings(): array
            {
                return (new QuizQuestionTemplateExport)->headings();
            }
        }, ExcelWriter::XLSX);

        $this->actingAs($teacher);
        Livewire::test(LearningManager::class)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('tab', 'quizzes')
            ->set('questionImport', UploadedFile::fake()->createWithContent('soal.xlsx', $content))
            ->call('importQuestions', $quiz->id)
            ->assertHasNoErrors()
            ->assertSee('Baris 5: kunci jawaban harus A, B, C, atau D.');

        $this->assertSame(3, $quiz->questions()->count());
        $first = $quiz->questions()->with('choices')->orderBy('id')->firstOrFail();
        $this->assertSame('96', $first->choices->firstWhere('is_correct', true)?->label);
        $this->assertSame('essay', $quiz->questions()->orderByDesc('id')->value('type'));
    }

    public function test_teacher_can_download_question_template(): void
    {
        [$teacher] = $this->context();

        $this->actingAs($teacher)->get(route('teacher.quiz-template'))->assertOk()->assertDownload('template-soal-kuis.xlsx');
    }

    private function quizWithQuestions(ClassSubject $classSubject, bool $shuffle, int $questionCount): Quiz
    {
        $quiz = Quiz::query()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Kuis Acak',
            'duration_minutes' => 30,
            'open_at' => now()->subHour(),
            'close_at' => now()->addHour(),
            'shuffle' => $shuffle,
        ]);

        for ($i = 1; $i <= $questionCount; $i++) {
            $question = $quiz->questions()->create(['question' => "Soal {$i}", 'type' => 'mc']);
            foreach (['A', 'B', 'C', 'D'] as $index => $label) {
                $question->choices()->create(['label' => "{$label}{$i}", 'is_correct' => $index === 0]);
            }
        }

        return $quiz;
    }

    /** @return array{User, list<User>, ClassSubject} */
    private function context(int $studentCount = 1): array
    {
        $teacher = User::factory()->teacher()->create();
        $students = User::factory()->count($studentCount)->student(mustChangePassword: false)->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);
        $class->students()->attach($students->pluck('id'));
        $subject = Subject::query()->create(['name' => 'Matematika', 'code' => 'MAT']);
        $classSubject = ClassSubject::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        return [$teacher, $students->all(), $classSubject];
    }
}
