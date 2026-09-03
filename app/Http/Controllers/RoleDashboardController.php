<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CalendarEvent;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleDashboardController extends Controller
{
    public function admin(): View
    {
        return view('dashboards.admin', [
            'studentCount' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'siswa'))->count(),
            'teacherCount' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'guru'))->count(),
            'activeClassCount' => SchoolClass::query()->whereHas('academicYear', fn ($query) => $query->where('is_active', true))->count(),
            'announcements' => Announcement::query()->with('schoolClass', 'creator')->latest()->limit(5)->get(),
            'events' => $this->events(),
        ]);
    }

    public function teacher(Request $request): View
    {
        $teacherId = $request->user()->id;
        $assignments = ClassSubject::query()->with('schoolClass', 'subject')->where('teacher_id', $teacherId)->get();
        $pendingGrades = AssignmentSubmission::query()
            ->whereHas('assignment.classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
            ->doesntHave('grade')->count();

        return view('dashboards.guru', [
            'teachingAssignments' => $assignments,
            'classCount' => $assignments->pluck('class_id')->unique()->count(),
            'pendingGrades' => $pendingGrades,
            'todaySchedules' => Schedule::query()->with('classSubject.schoolClass', 'classSubject.subject')
                ->where('day', $this->todayName())
                ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
                ->orderBy('start_time')->get(),
            'events' => $this->events(),
        ]);
    }

    public function student(Request $request): View
    {
        $studentId = $request->user()->id;
        $classIds = SchoolClass::query()
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->whereHas('students', fn ($query) => $query->whereKey($studentId))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        return view('dashboards.siswa', [
            'todaySchedules' => Schedule::query()->with('classSubject.subject', 'classSubject.teacher')
                ->where('day', $this->todayName())
                ->whereHas('classSubject', fn ($query) => $query->whereIn('class_id', $classIds))
                ->orderBy('start_time')->get(),
            'upcomingAssignments' => Assignment::query()->with('classSubject.subject', 'classSubject.teacher')
                ->whereHas('classSubject', fn ($query) => $query->whereIn('class_id', $classIds))
                ->whereBetween('deadline', [now(), now()->addDays(7)])
                ->whereDoesntHave('submissions', fn ($query) => $query->where('student_id', $studentId))
                ->orderBy('deadline')->limit(5)->get(),
            'announcements' => $this->studentAnnouncements($classIds->all())->latest()->limit(5)->get(),
            'recentGrades' => Grade::query()->with('classSubject.subject')->where('student_id', $studentId)->latest()->limit(5)->get(),
            'events' => $this->events(),
        ]);
    }

    private function todayName(): string
    {
        return ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'][now()->format('l')];
    }

    /** @return Collection<int, CalendarEvent> */
    private function events(): Collection
    {
        return CalendarEvent::query()->whereDate('date', '>=', today())->orderBy('date')->limit(5)->get();
    }

    /**
     * @param  array<int, int>  $classIds
     * @return Builder<Announcement>
     */
    private function studentAnnouncements(array $classIds): Builder
    {
        return Announcement::query()->with('schoolClass', 'creator')->where(function ($query) use ($classIds): void {
            $query->where('target', 'all')->orWhere(function ($nested) use ($classIds): void {
                $nested->where('target', 'class')->whereIn('class_id', $classIds);
            });
        });
    }
}
