<?php

namespace App\Livewire\Student;

use App\Livewire\Concerns\ResolvesStudent;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.siswa')]
#[Title('Ruang Belajar')]
class LearningCenter extends Component
{
    use ResolvesStudent;
    use WithFileUploads;

    #[Url]
    public string $classSubjectId = '';

    #[Url]
    public string $tab = 'materials';

    #[Url]
    public string $assignmentId = '';

    public ?TemporaryUploadedFile $submissionFile = null;

    public function mount(): void
    {
        if ($this->classSubjectId === '') {
            $first = $this->subjectsQuery()->first();
            $this->classSubjectId = $first ? (string) $first->id : '';
        }
    }

    public function submitAssignment(int $assignmentId): void
    {
        // Mengumpulkan tugas adalah aksi, bukan membaca data: selalu atas nama
        // siswa yang sedang login, tidak pernah atas nama siswa yang sedang dilihat.
        $studentId = $this->actingStudentId();

        $assignment = Assignment::query()
            ->whereHas('classSubject.schoolClass.students', fn ($query) => $query->whereKey($studentId))
            ->findOrFail($assignmentId);

        if (now()->isAfter($assignment->deadline)) {
            throw ValidationException::withMessages(['submissionFile' => 'Batas waktu pengumpulan sudah lewat.']);
        }

        $this->validate(['submissionFile' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,jpg,jpeg,png', 'max:20480']]);
        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->first();
        $path = $this->submissionFile?->store("learning/submissions/{$assignment->id}/".$studentId, 'local');

        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
        }

        AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $studentId],
            ['file_path' => $path, 'submitted_at' => now()],
        );
        $this->reset('submissionFile');
        session()->flash('learning_status', 'Jawaban tugas berhasil dikumpulkan.');
    }

    public function render(): View
    {
        $studentId = $this->viewedStudentId();

        $selected = $this->classSubjectId !== ''
            ? $this->subjectsQuery()->with([
                'schoolClass', 'subject', 'teacher', 'materials.files',
                'assignments' => fn ($query) => $query->orderBy('deadline'),
                'assignments.submissions' => fn ($query) => $query->where('student_id', $studentId),
                'assignments.submissions.grade', 'quizzes.questions',
                'quizzes.attempts' => fn ($query) => $query->where('student_id', $studentId),
            ])->find($this->classSubjectId)
            : null;

        return view('livewire.student.learning-center', [
            'subjects' => $this->subjectsQuery()->with('schoolClass', 'subject', 'teacher')->get(),
            'selected' => $selected,
        ]);
    }

    /** @return Builder<ClassSubject> */
    private function subjectsQuery(): Builder
    {
        $studentId = $this->viewedStudentId();

        return ClassSubject::query()
            ->whereHas('schoolClass.students', fn ($query) => $query->whereKey($studentId))
            ->orderBy('subject_id');
    }
}
