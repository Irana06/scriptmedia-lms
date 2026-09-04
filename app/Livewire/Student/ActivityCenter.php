<?php

namespace App\Livewire\Student;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CalendarEvent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.siswa')]
#[Title('Pusat Aktivitas')]
class ActivityCenter extends Component
{
    #[Url]
    public string $tab = 'todo';

    public function render(): View
    {
        $studentId = (int) Auth::id();
        $classIds = $this->activeClassIds($studentId);

        $assignmentScope = fn (Builder $query): Builder => $query
            ->whereHas('classSubject', fn (Builder $classSubject): Builder => $classSubject->whereIn('class_id', $classIds));

        $pendingAssignments = Assignment::query()
            ->with('classSubject.subject', 'classSubject.teacher')
            ->where($assignmentScope)
            ->where('deadline', '>=', now())
            ->whereDoesntHave('submissions', fn (Builder $query): Builder => $query->where('student_id', $studentId))
            ->orderBy('deadline')
            ->get();

        $overdueAssignments = Assignment::query()
            ->with('classSubject.subject', 'classSubject.teacher')
            ->where($assignmentScope)
            ->where('deadline', '<', now())
            ->whereDoesntHave('submissions', fn (Builder $query): Builder => $query->where('student_id', $studentId))
            ->orderByDesc('deadline')
            ->get();

        $activeQuizzes = Quiz::query()
            ->with('classSubject.subject')
            ->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
            ->where('open_at', '<=', now())
            ->where('close_at', '>=', now())
            ->whereDoesntHave('attempts', fn (Builder $query): Builder => $query->where('student_id', $studentId))
            ->orderBy('close_at')
            ->get();

        $submissions = AssignmentSubmission::query()
            ->with('assignment.classSubject.subject', 'grade')
            ->where('student_id', $studentId)
            ->whereHas('assignment', $assignmentScope)
            ->latest('submitted_at')
            ->limit(20)
            ->get();

        $quizAttempts = QuizAttempt::query()
            ->with('quiz.classSubject.subject')
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->whereHas('quiz.classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
            ->latest('submitted_at')
            ->limit(20)
            ->get();

        $totalActivities = Assignment::query()->where($assignmentScope)->count()
            + Quiz::query()->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))->count();
        $completedActivities = AssignmentSubmission::query()
            ->where('student_id', $studentId)
            ->whereHas('assignment', $assignmentScope)
            ->count()
            + QuizAttempt::query()
                ->where('student_id', $studentId)
                ->whereNotNull('submitted_at')
                ->whereHas('quiz.classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
                ->count();

        return view('livewire.student.activity-center', [
            'pendingAssignments' => $pendingAssignments,
            'overdueAssignments' => $overdueAssignments,
            'activeQuizzes' => $activeQuizzes,
            'submissions' => $submissions,
            'quizAttempts' => $quizAttempts,
            'announcements' => $this->announcements($classIds)->latest()->limit(20)->get(),
            'events' => CalendarEvent::query()->whereDate('date', '>=', today())->orderBy('date')->limit(10)->get(),
            'todoCount' => $pendingAssignments->count() + $overdueAssignments->count() + $activeQuizzes->count(),
            'completedActivities' => $completedActivities,
            'totalActivities' => $totalActivities,
            'progress' => $totalActivities > 0 ? min(100, (int) round($completedActivities / $totalActivities * 100)) : 0,
        ]);
    }

    /** @return Collection<int, int> */
    private function activeClassIds(int $studentId): Collection
    {
        return SchoolClass::query()
            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereHas('students', fn (Builder $query): Builder => $query->whereKey($studentId))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();
    }

    /**
     * @param  Collection<int, int>  $classIds
     * @return Builder<Announcement>
     */
    private function announcements(Collection $classIds): Builder
    {
        return Announcement::query()->with('schoolClass', 'creator')->where(function (Builder $query) use ($classIds): void {
            $query->where('target', 'all')->orWhere(function (Builder $nested) use ($classIds): void {
                $nested->where('target', 'class')->whereIn('class_id', $classIds);
            });
        });
    }
}
