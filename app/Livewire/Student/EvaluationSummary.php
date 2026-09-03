<?php

namespace App\Livewire\Student;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.siswa')]
#[Title('Nilai & Presensi')]
class EvaluationSummary extends Component
{
    #[Url]
    public string $semesterId = '';

    public function mount(): void
    {
        if ($this->semesterId === '') {
            $semester = Semester::query()->whereHas('academicYear', fn ($query) => $query->where('is_active', true))->orderBy('start_date')->first()
                ?? Semester::query()->latest('start_date')->first();
            $this->semesterId = $semester ? (string) $semester->id : '';
        }
    }

    public function render(): View
    {
        $semester = $this->semesterId !== '' ? Semester::query()->with('academicYear')->find($this->semesterId) : null;
        $grades = $semester
            ? Grade::query()->with('classSubject.subject', 'classSubject.teacher')->where('student_id', Auth::id())->where('semester_id', $semester->id)->get()
            : collect();
        $attendance = $semester
            ? Attendance::query()->where('student_id', Auth::id())->whereBetween('date', [$semester->start_date, $semester->end_date])->get()
            : collect();

        return view('livewire.student.evaluation-summary', [
            'semesters' => Semester::query()->with('academicYear')->latest('start_date')->get(),
            'semester' => $semester,
            'grades' => $grades,
            'attendanceCounts' => collect(['hadir', 'izin', 'sakit', 'alpa'])->mapWithKeys(fn ($status): array => [$status => $attendance->where('status', $status)->count()]),
        ]);
    }
}
