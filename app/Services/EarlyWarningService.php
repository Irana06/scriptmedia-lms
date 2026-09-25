<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use Illuminate\Support\Collection;

/**
 * Ringkasan untuk kepala sekolah/admin: siswa yang perlu perhatian dan statistik
 * per kelas di tahun ajaran aktif. Dihitung dengan sedikit query agregat, bukan
 * per siswa, supaya dashboard tetap cepat di sekolah dengan ratusan siswa.
 */
class EarlyWarningService
{
    public const ATTENDANCE_THRESHOLD = 80;

    public const MISSING_ASSIGNMENT_THRESHOLD = 3;

    /**
     * @return array{
     *     atRisk: list<array{id: int, name: string, className: string, attendanceRate: int|null, belowKkm: int, missing: int, reasons: list<string>}>,
     *     classStats: list<array{name: string, students: int, attendanceRate: int|null, averageScore: float|null}>,
     *     periodLabel: string
     * }
     */
    public function summary(): array
    {
        $year = AcademicYear::query()->where('is_active', true)->first();

        if ($year === null) {
            return ['atRisk' => [], 'classStats' => [], 'periodLabel' => 'Belum ada tahun ajaran aktif'];
        }

        $semester = Semester::query()->where('academic_year_id', $year->id)
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->first();
        $from = $semester !== null ? $semester->start_date : today()->subDays(30);
        $periodLabel = $semester !== null ? "Semester {$semester->name} {$year->year_label}" : '30 hari terakhir';

        $classes = SchoolClass::query()->where('academic_year_id', $year->id)->with('students:id,name')->orderBy('name')->get();
        $classIds = $classes->pluck('id')->all();

        $attendance = Attendance::query()
            ->whereIn('class_id', $classIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', today())
            ->selectRaw('student_id, class_id, count(*) as total, sum(case when status = ? then 1 else 0 end) as present', ['hadir'])
            ->groupBy('student_id', 'class_id')
            ->toBase()
            ->get();
        $attendanceByStudent = $attendance->keyBy('student_id');

        $grades = $semester !== null
            ? Grade::query()->where('semester_id', $semester->id)->with('classSubject.subject:id,kkm')->get(['id', 'student_id', 'class_subject_id', 'final_score'])
            : collect();

        $missingByStudent = $this->missingAssignments($classes);

        $atRisk = [];
        foreach ($classes as $class) {
            foreach ($class->students as $student) {
                $row = $attendanceByStudent->get($student->id);
                $rate = $row !== null && (int) $row->total > 0 ? (int) round((int) $row->present / (int) $row->total * 100) : null;
                $belowKkm = $grades->where('student_id', $student->id)
                    ->filter(fn (Grade $grade): bool => (float) $grade->final_score < $grade->classSubject->subject->kkm)
                    ->count();
                $missing = $missingByStudent[$student->id] ?? 0;

                $reasons = [];
                if ($rate !== null && $rate < self::ATTENDANCE_THRESHOLD) {
                    $reasons[] = "Kehadiran {$rate}%";
                }
                if ($belowKkm > 0) {
                    $reasons[] = "{$belowKkm} mapel di bawah KKM";
                }
                if ($missing >= self::MISSING_ASSIGNMENT_THRESHOLD) {
                    $reasons[] = "{$missing} tugas tidak dikumpulkan";
                }

                if ($reasons !== []) {
                    $atRisk[] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'className' => $class->name,
                        'attendanceRate' => $rate,
                        'belowKkm' => $belowKkm,
                        'missing' => $missing,
                        'reasons' => $reasons,
                    ];
                }
            }
        }

        $classStats = [];
        foreach ($classes as $class) {
            $rows = $attendance->where('class_id', $class->id);
            $total = (int) $rows->sum('total');
            $classGrades = $grades->whereIn('student_id', $class->students->pluck('id')->all());

            $classStats[] = [
                'name' => $class->name,
                'students' => $class->students->count(),
                'attendanceRate' => $total > 0 ? (int) round((int) $rows->sum('present') / $total * 100) : null,
                'averageScore' => $classGrades->isNotEmpty() ? round((float) $classGrades->avg('final_score'), 1) : null,
            ];
        }

        // Paling banyak alasan dulu, lalu kehadiran terendah.
        usort($atRisk, fn (array $a, array $b): int => [count($b['reasons']), $b['attendanceRate'] === null ? 0 : 100 - $b['attendanceRate']]
            <=> [count($a['reasons']), $a['attendanceRate'] === null ? 0 : 100 - $a['attendanceRate']]);

        return [
            'atRisk' => $atRisk,
            'classStats' => $classStats,
            'periodLabel' => $periodLabel,
        ];
    }

    /**
     * Tugas yang tenggatnya sudah lewat tanpa kiriman, per siswa.
     *
     * @param  Collection<int, SchoolClass>  $classes
     * @return array<int, int>
     */
    private function missingAssignments(Collection $classes): array
    {
        $missing = [];
        $assignments = Assignment::query()
            ->whereHas('classSubject', fn ($query) => $query->whereIn('class_id', $classes->pluck('id')->all()))
            ->where('deadline', '<', now())
            ->with(['classSubject:id,class_id', 'submissions:id,assignment_id,student_id'])
            ->get(['id', 'class_subject_id']);

        foreach ($assignments as $assignment) {
            $class = $classes->firstWhere('id', $assignment->classSubject->class_id);
            $submitted = $assignment->submissions->pluck('student_id')->all();

            if ($class === null) {
                continue;
            }

            foreach ($class->students as $student) {
                if (! in_array($student->id, $submitted, true)) {
                    $missing[$student->id] = ($missing[$student->id] ?? 0) + 1;
                }
            }
        }

        return $missing;
    }
}
