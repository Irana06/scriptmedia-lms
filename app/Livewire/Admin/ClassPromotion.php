<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Services\ClassPromotionService;
use App\Support\ClassLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guru-admin')]
#[Title('Kenaikan Kelas')]
class ClassPromotion extends Component
{
    public string $sourceYearId = '';

    public string $targetYearId = '';

    /** @var array<int, string> id kelas asal => id kelas tujuan | 'lulus' | '' */
    public array $classTargets = [];

    /** @var array<int, string> id siswa => pengecualian; '' berarti ikut kelasnya */
    public array $studentOverrides = [];

    public bool $activateTarget = true;

    /** @var array{moved: int, graduated: int, skipped: int}|null */
    public ?array $summary = null;

    public function mount(): void
    {
        $source = AcademicYear::query()->where('is_active', true)->first();
        $this->sourceYearId = $source ? (string) $source->id : '';
        $target = AcademicYear::query()->where('year_label', '>', $source->year_label ?? '')->orderBy('year_label')->first();
        $this->targetYearId = $target ? (string) $target->id : '';
        $this->suggestTargets();
    }

    public function updatedSourceYearId(): void
    {
        $this->suggestTargets();
    }

    public function updatedTargetYearId(): void
    {
        $this->suggestTargets();
    }

    /**
     * Tahun ajaran baru biasanya berisi nama kelas yang sama (7A, 8A, 9A) karena
     * angkatan baru masuk ke tingkat terbawah. Buat yang belum ada, tanpa wali kelas.
     */
    public function copyClassesToTarget(): void
    {
        [$source, $target] = $this->years();
        $existing = SchoolClass::query()->where('academic_year_id', $target->id)->pluck('name')->all();

        $created = 0;
        foreach (SchoolClass::query()->where('academic_year_id', $source->id)->orderBy('name')->pluck('name') as $name) {
            if (! in_array($name, $existing, true)) {
                SchoolClass::query()->create(['academic_year_id' => $target->id, 'name' => $name]);
                $created++;
            }
        }

        $this->suggestTargets();
        session()->flash('promotion_status', "{$created} kelas dibuat di tahun ajaran {$target->year_label}. Atur wali kelasnya di Struktur Akademik.");
    }

    public function promote(ClassPromotionService $service): void
    {
        [$source, $target] = $this->years();
        $sourceClasses = $this->sourceClasses($source);

        $studentTargets = [];
        foreach ($sourceClasses as $class) {
            $classTarget = (string) ($this->classTargets[$class->id] ?? '');

            foreach ($class->students as $student) {
                $override = (string) ($this->studentOverrides[$student->id] ?? '');
                $studentTargets[$student->id] = $override !== '' ? $override : $classTarget;
            }
        }

        if (collect($studentTargets)->every(fn (string $value): bool => $value === ClassPromotionService::SKIP)) {
            throw ValidationException::withMessages(['classTargets' => 'Belum ada kelas tujuan yang dipilih.']);
        }

        $this->summary = DB::transaction(function () use ($service, $target, $studentTargets): array {
            $summary = $service->run($target, $studentTargets);

            if ($this->activateTarget) {
                AcademicYear::query()->update(['is_active' => false]);
                $target->update(['is_active' => true]);
            }

            return $summary;
        });
    }

    public function render(): View
    {
        $years = AcademicYear::query()->orderByDesc('year_label')->get();
        $source = $years->firstWhere('id', (int) $this->sourceYearId);
        $target = $years->firstWhere('id', (int) $this->targetYearId);

        return view('livewire.admin.class-promotion', [
            'years' => $years,
            'sourceClasses' => $source ? $this->sourceClasses($source) : collect(),
            'targetClasses' => $target ? SchoolClass::query()->where('academic_year_id', $target->id)->orderBy('name')->get() : collect(),
            'targetYear' => $target,
        ]);
    }

    private function suggestTargets(): void
    {
        $this->classTargets = [];
        $this->studentOverrides = [];
        $this->summary = null;

        $source = AcademicYear::query()->find($this->sourceYearId);
        $target = AcademicYear::query()->find($this->targetYearId);

        if ($source === null || $target === null) {
            return;
        }

        $targetByName = SchoolClass::query()->where('academic_year_id', $target->id)->pluck('id', 'name');
        $sourceClasses = SchoolClass::query()->where('academic_year_id', $source->id)->get();
        $topLevel = $sourceClasses->map(fn (SchoolClass $class): ?int => ClassLevel::parse($class->name)['level'] ?? null)->filter()->max();

        foreach ($sourceClasses as $class) {
            $next = ClassLevel::next($class->name);
            $level = ClassLevel::parse($class->name)['level'] ?? null;

            $this->classTargets[$class->id] = match (true) {
                $next !== null && $targetByName->has($next) => (string) $targetByName[$next],
                $level !== null && $level === $topLevel => ClassPromotionService::GRADUATE,
                default => ClassPromotionService::SKIP,
            };
        }
    }

    /** @return array{AcademicYear, AcademicYear} */
    private function years(): array
    {
        $source = AcademicYear::query()->find($this->sourceYearId);
        $target = AcademicYear::query()->find($this->targetYearId);

        if ($source === null || $target === null || $source->is($target)) {
            throw ValidationException::withMessages(['targetYearId' => 'Pilih tahun ajaran asal dan tujuan yang berbeda.']);
        }

        return [$source, $target];
    }

    /** @return Collection<int, SchoolClass> */
    private function sourceClasses(AcademicYear $source): Collection
    {
        return SchoolClass::query()
            ->with(['students' => fn ($query) => $query->orderBy('name')])
            ->where('academic_year_id', $source->id)
            ->orderBy('name')
            ->get();
    }
}
