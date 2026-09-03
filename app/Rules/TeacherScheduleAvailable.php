<?php

namespace App\Rules;

use App\Models\ClassSubject;
use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TeacherScheduleAvailable implements ValidationRule
{
    public function __construct(
        private readonly int $classSubjectId,
        private readonly string $day,
        private readonly string $startTime,
        private readonly ?int $ignoreScheduleId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $teacherId = ClassSubject::query()->whereKey($this->classSubjectId)->firstOrFail()->teacher_id;

        $conflict = Schedule::query()
            ->when($this->ignoreScheduleId, fn ($query) => $query->whereKeyNot($this->ignoreScheduleId))
            ->where('day', $this->day)
            ->where('start_time', '<', (string) $value)
            ->where('end_time', '>', $this->startTime)
            ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
            ->exists();

        if ($conflict) {
            $fail('Guru tersebut sudah memiliki jadwal lain pada waktu yang bertabrakan.');
        }
    }
}
