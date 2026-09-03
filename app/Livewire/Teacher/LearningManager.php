<?php

namespace App\Livewire\Teacher;

use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Services\QuizScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guru-admin')]
#[Title('Pembelajaran')]
class LearningManager extends Component
{
    use WithFileUploads;

    #[Url]
    public string $classSubjectId = '';

    #[Url]
    public string $tab = 'materials';

    public string $materialTitle = '';

    public string $materialDescription = '';

    public string $materialType = 'pdf';

    public string $materialLink = '';

    public ?TemporaryUploadedFile $materialUpload = null;

    public string $materialOrder = '1';

    public string $assignmentTitle = '';

    public string $assignmentDescription = '';

    public string $assignmentDeadline = '';

    public string $gradeScore = '';

    public string $gradeFeedback = '';

    public ?int $gradingSubmissionId = null;

    public string $quizTitle = '';

    public string $quizDuration = '30';

    public string $quizOpenAt = '';

    public string $quizCloseAt = '';

    public string $questionText = '';

    public string $questionType = 'mc';

    /** @var list<string> */
    public array $choices = ['', '', '', ''];

    public string $correctChoice = '0';

    public string $essayScore = '';

    public function mount(): void
    {
        if ($this->classSubjectId === '') {
            $first = $this->assignmentsQuery()->first();
            $this->classSubjectId = $first ? (string) $first->id : '';
        }
    }

    public function saveMaterial(): void
    {
        $this->selectedAssignment();
        $rules = [
            'materialTitle' => ['required', 'string', 'max:255'],
            'materialDescription' => ['nullable', 'string', 'max:5000'],
            'materialType' => ['required', Rule::in(['pdf', 'video', 'link'])],
            'materialOrder' => ['required', 'integer', 'min:0', 'max:999'],
        ];
        $rules[$this->materialType === 'link' ? 'materialLink' : 'materialUpload'] = $this->materialType === 'link'
            ? ['required', 'url:http,https', 'max:2048']
            : ['required', 'file', $this->materialType === 'pdf' ? 'mimes:pdf' : 'mimes:mp4,webm,mov', 'max:51200'];
        $validated = $this->validate($rules);

        DB::transaction(function () use ($validated): void {
            $material = Material::query()->create([
                'class_subject_id' => $this->classSubjectId,
                'title' => $validated['materialTitle'],
                'description' => $validated['materialDescription'] ?: null,
                'order' => $validated['materialOrder'],
            ]);

            $path = $this->materialType === 'link'
                ? $validated['materialLink']
                : $this->materialUpload?->store("learning/materials/{$material->id}", 'local');

            $material->files()->create(['type' => $this->materialType, 'file_path' => $path]);
        });

        $this->reset('materialTitle', 'materialDescription', 'materialLink', 'materialUpload');
        $this->materialType = 'pdf';
        session()->flash('learning_status', 'Materi berhasil ditambahkan.');
    }

    public function deleteMaterial(int $id): void
    {
        $material = Material::query()->with('files')->findOrFail($id);
        $this->authorizeOwned($material->class_subject_id);
        foreach ($material->files as $file) {
            if ($file->type !== 'link') {
                Storage::disk('local')->delete($file->file_path);
            }
        }
        $material->delete();
    }

    public function saveAssignment(): void
    {
        $this->selectedAssignment();
        $validated = $this->validate([
            'assignmentTitle' => ['required', 'string', 'max:255'],
            'assignmentDescription' => ['nullable', 'string', 'max:5000'],
            'assignmentDeadline' => ['required', 'date', 'after:now'],
        ]);
        Assignment::query()->create([
            'class_subject_id' => $this->classSubjectId,
            'title' => $validated['assignmentTitle'],
            'description' => $validated['assignmentDescription'] ?: null,
            'deadline' => $validated['assignmentDeadline'],
        ]);
        $this->reset('assignmentTitle', 'assignmentDescription', 'assignmentDeadline');
        session()->flash('learning_status', 'Tugas berhasil dibuat.');
    }

    public function deleteAssignment(int $id): void
    {
        $assignment = Assignment::query()->with('submissions')->findOrFail($id);
        $this->authorizeOwned($assignment->class_subject_id);
        foreach ($assignment->submissions as $submission) {
            Storage::disk('local')->delete($submission->file_path);
        }
        $assignment->delete();
    }

    public function editGrade(int $submissionId): void
    {
        $submission = $this->ownedSubmission($submissionId);
        $grade = AssignmentGrade::query()->where('submission_id', $submission->id)->first();
        $this->gradingSubmissionId = $submission->id;
        if ($grade === null) {
            $this->gradeScore = '';
            $this->gradeFeedback = '';
        } else {
            $this->gradeScore = (string) $grade->score;
            $this->gradeFeedback = $grade->feedback ?? '';
        }
    }

    public function saveGrade(): void
    {
        $submission = $this->ownedSubmission((int) $this->gradingSubmissionId);
        $validated = $this->validate([
            'gradeScore' => ['required', 'numeric', 'min:0', 'max:100'],
            'gradeFeedback' => ['nullable', 'string', 'max:3000'],
        ]);
        AssignmentGrade::query()->updateOrCreate(
            ['submission_id' => $submission->id],
            ['score' => $validated['gradeScore'], 'feedback' => $validated['gradeFeedback'] ?: null],
        );
        $this->reset('gradingSubmissionId', 'gradeScore', 'gradeFeedback');
        session()->flash('learning_status', 'Nilai tugas tersimpan.');
    }

    public function saveQuiz(): void
    {
        $this->selectedAssignment();
        $validated = $this->validate([
            'quizTitle' => ['required', 'string', 'max:255'],
            'quizDuration' => ['required', 'integer', 'min:1', 'max:240'],
            'quizOpenAt' => ['required', 'date'],
            'quizCloseAt' => ['required', 'date', 'after:quizOpenAt'],
        ]);
        Quiz::query()->create([
            'class_subject_id' => $this->classSubjectId,
            'title' => $validated['quizTitle'],
            'duration_minutes' => $validated['quizDuration'],
            'open_at' => $validated['quizOpenAt'],
            'close_at' => $validated['quizCloseAt'],
        ]);
        $this->reset('quizTitle', 'quizOpenAt', 'quizCloseAt');
        $this->quizDuration = '30';
        session()->flash('learning_status', 'Kuis berhasil dibuat.');
    }

    public function addQuestion(int $quizId): void
    {
        $quiz = Quiz::query()->findOrFail($quizId);
        $this->authorizeOwned($quiz->class_subject_id);
        $rules = [
            'questionText' => ['required', 'string', 'max:5000'],
            'questionType' => ['required', Rule::in(['mc', 'essay'])],
        ];
        if ($this->questionType === 'mc') {
            $rules['choices'] = ['array', 'size:4'];
            $rules['choices.*'] = ['required', 'string', 'max:1000', 'distinct'];
            $rules['correctChoice'] = ['required', 'integer', 'between:0,3'];
        }
        $this->validate($rules);

        DB::transaction(function () use ($quiz): void {
            $question = $quiz->questions()->create(['question' => $this->questionText, 'type' => $this->questionType]);
            if ($this->questionType === 'mc') {
                foreach ($this->choices as $index => $label) {
                    $question->choices()->create(['label' => $label, 'is_correct' => $index === (int) $this->correctChoice]);
                }
            }
        });
        $this->reset('questionText');
        $this->choices = ['', '', '', ''];
        $this->correctChoice = '0';
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = QuizQuestion::query()->with('quiz')->findOrFail($questionId);
        $this->authorizeOwned($question->quiz->class_subject_id);
        $question->delete();
    }

    public function gradeEssay(int $answerId, QuizScoringService $scoring): void
    {
        $answer = QuizAnswer::query()->with('attempt.quiz')->findOrFail($answerId);
        $this->authorizeOwned($answer->attempt->quiz->class_subject_id);
        $validated = $this->validate(['essayScore' => ['required', 'numeric', 'min:0', 'max:1']]);
        $answer->update(['score' => $validated['essayScore']]);
        $scoring->recalculate($answer->attempt);
        $this->reset('essayScore');
    }

    public function deleteQuiz(int $id): void
    {
        $quiz = Quiz::query()->findOrFail($id);
        $this->authorizeOwned($quiz->class_subject_id);
        $quiz->delete();
    }

    public function render(): View
    {
        $selected = $this->classSubjectId !== ''
            ? $this->assignmentsQuery()->with([
                'schoolClass.students', 'subject', 'materials.files',
                'assignments.submissions.student', 'assignments.submissions.grade',
                'quizzes.questions.choices', 'quizzes.attempts.student',
                'quizzes.attempts.answers.question',
            ])->find($this->classSubjectId)
            : null;

        return view('livewire.teacher.learning-manager', [
            'teachingAssignments' => $this->assignmentsQuery()->with('schoolClass', 'subject')->get(),
            'selected' => $selected,
        ]);
    }

    /** @return Builder<ClassSubject> */
    private function assignmentsQuery(): Builder
    {
        return ClassSubject::query()->where('teacher_id', Auth::id())->orderBy('class_id');
    }

    private function selectedAssignment(): ClassSubject
    {
        return $this->assignmentsQuery()->findOrFail($this->classSubjectId);
    }

    private function authorizeOwned(int $classSubjectId): void
    {
        if (! $this->assignmentsQuery()->whereKey($classSubjectId)->exists()) {
            throw ValidationException::withMessages(['classSubjectId' => 'Anda tidak mengajar kelas ini.']);
        }
    }

    private function ownedSubmission(int $id): AssignmentSubmission
    {
        return AssignmentSubmission::query()
            ->with('grade')
            ->whereHas('assignment.classSubject', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->findOrFail($id);
    }
}
