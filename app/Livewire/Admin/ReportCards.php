<?php

namespace App\Livewire\Admin;

use App\Models\SchoolClass;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guru-admin')]
#[Title('Rapor Siswa')]
class ReportCards extends Component
{
    public string $semesterId = '';

    public string $classId = '';

    public string $studentId = '';

    public function mount(): void
    {
        $semester = Semester::query()->whereHas('academicYear', fn ($query) => $query->where('is_active', true))->orderBy('start_date')->first()
            ?? Semester::query()->latest('start_date')->first();
        $this->semesterId = $semester ? (string) $semester->id : '';
        $this->selectFirstClass();
    }

    /**
     * Admin melihat semua kelas; guru hanya kelas yang diwalikannya, supaya guru
     * tidak bisa membuka rapor siswa kelas lain lewat halaman ini.
     *
     * @param  Builder<SchoolClass>  $query
     * @return Builder<SchoolClass>
     */
    private function scopeToActor(Builder $query): Builder
    {
        $user = Auth::user();

        return $user?->hasRole('admin') ? $query : $query->where('homeroom_teacher_id', $user?->id);
    }

    public function updatedSemesterId(): void
    {
        $this->selectFirstClass();
    }

    public function updatedClassId(): void
    {
        $this->selectFirstStudent();
    }

    public function render(): View
    {
        $semester = $this->semesterId !== '' ? Semester::query()->find($this->semesterId) : null;
        $classes = $semester
            ? $this->scopeToActor(SchoolClass::query()->where('academic_year_id', $semester->academic_year_id))->orderBy('name')->get()
            : collect();
        // Kelas dipilih dari daftar yang sudah dibatasi di atas, bukan dicari ulang
        // tanpa batasan — supaya guru tidak bisa membuka rapor kelas lain dengan
        // menitipkan classId yang bukan miliknya.
        $selectedClass = $this->classId !== '' ? $classes->firstWhere('id', (int) $this->classId) : null;
        $students = $selectedClass?->students()->orderBy('name')->get() ?? collect();

        return view('livewire.admin.report-cards', [
            'semesters' => Semester::query()->with('academicYear')->latest('start_date')->get(),
            'classes' => $classes,
            'students' => $students,
        ]);
    }

    private function selectFirstClass(): void
    {
        $semester = Semester::query()->find($this->semesterId);
        $class = $semester
            ? $this->scopeToActor(SchoolClass::query()->where('academic_year_id', $semester->academic_year_id))->orderBy('name')->first()
            : null;
        $this->classId = $class ? (string) $class->id : '';
        $this->selectFirstStudent();
    }

    private function selectFirstStudent(): void
    {
        $student = $this->classId !== '' ? SchoolClass::query()->find($this->classId)?->students()->orderBy('name')->first() : null;
        $this->studentId = $student ? (string) $student->id : '';
    }
}
