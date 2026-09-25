<?php

namespace App\Exports;

use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Leger nilai: satu baris per siswa, satu kolom per mata pelajaran — format
 * yang biasa dipakai operator untuk dipindahkan ke e-Rapor.
 */
class GradeLedgerExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /** @var list<ClassSubject> */
    private array $subjects;

    public function __construct(private readonly SchoolClass $schoolClass, private readonly Semester $semester)
    {
        $this->subjects = array_values($schoolClass->classSubjects()->with('subject')->get()->sortBy('subject.name')->all());
    }

    /** @return list<string> */
    public function headings(): array
    {
        $subjectNames = array_map(fn (ClassSubject $classSubject): string => $classSubject->subject->name, $this->subjects);

        return ['No', 'NISN', 'NIS', 'Nama', ...$subjectNames, 'Jumlah', 'Rata-rata', 'Mapel di bawah KKM'];
    }

    /** @return list<list<int|float|string|null>> */
    public function array(): array
    {
        $grades = Grade::query()
            ->where('semester_id', $this->semester->id)
            ->whereIn('class_subject_id', array_map(fn (ClassSubject $classSubject): int => $classSubject->id, $this->subjects))
            ->get()
            ->groupBy('student_id');

        $rows = [];
        foreach ($this->schoolClass->students()->orderBy('name')->get() as $index => $student) {
            $studentGrades = $grades->get($student->id, collect())->keyBy('class_subject_id');
            $scores = [];
            $belowKkm = 0;

            foreach ($this->subjects as $classSubject) {
                $score = $studentGrades->get($classSubject->id)?->final_score;
                $scores[] = $score !== null ? (float) $score : null;
                if ($score !== null && (float) $score < $classSubject->subject->kkm) {
                    $belowKkm++;
                }
            }

            $filled = array_values(array_filter($scores, fn (?float $score): bool => $score !== null));
            $rows[] = [
                $index + 1,
                $student->nisn,
                $student->nis,
                $student->name,
                ...$scores,
                $filled !== [] ? round(array_sum($filled), 2) : null,
                $filled !== [] ? round(array_sum($filled) / count($filled), 2) : null,
                $belowKkm,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return "Leger {$this->schoolClass->name}";
    }
}
