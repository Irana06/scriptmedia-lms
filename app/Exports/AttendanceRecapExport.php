<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Semester;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceRecapExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly SchoolClass $schoolClass, private readonly Semester $semester) {}

    /** @return list<string> */
    public function headings(): array
    {
        return ['No', 'NISN', 'NIS', 'Nama', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total hari', 'Kehadiran (%)'];
    }

    /** @return list<list<int|float|string|null>> */
    public function array(): array
    {
        $records = Attendance::query()
            ->where('class_id', $this->schoolClass->id)
            ->whereDate('date', '>=', $this->semester->start_date)
            ->whereDate('date', '<=', $this->semester->end_date)
            ->get(['student_id', 'status'])
            ->groupBy('student_id');

        $rows = [];
        foreach ($this->schoolClass->students()->orderBy('name')->get() as $index => $student) {
            $statuses = $records->get($student->id, collect())->countBy('status');
            $total = (int) $statuses->sum();
            $present = (int) $statuses->get('hadir', 0);

            $rows[] = [
                $index + 1,
                $student->nisn,
                $student->nis,
                $student->name,
                $present,
                (int) $statuses->get('izin', 0),
                (int) $statuses->get('sakit', 0),
                (int) $statuses->get('alpa', 0),
                $total,
                $total > 0 ? round($present / $total * 100, 1) : null,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return "Presensi {$this->schoolClass->name}";
    }
}
