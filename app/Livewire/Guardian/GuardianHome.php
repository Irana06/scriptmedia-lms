<?php

namespace App\Livewire\Guardian;

use App\Auth\StudentAccess;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\GuardianLink;
use App\Models\Quiz;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use App\Support\SchoolDay;
use App\Support\StudentLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.ortu')]
#[Title('Beranda Orang Tua')]
class GuardianHome extends Component
{
    /**
     * Jawaban yang sama untuk NISN tak dikenal dan nama yang tidak cocok, supaya
     * formulir ini tidak bisa dipakai untuk menebak data siswa.
     */
    private const MISMATCH = 'Data anak tidak cocok dengan data sekolah. Periksa kembali NISN atau NIS dan nama lengkap sesuai rapor.';

    public string $studentIdentifier = '';

    public string $studentName = '';

    public string $relationship = '';

    public function mount(): void
    {
        $this->guardian();
    }

    public function selectChild(int $studentId): void
    {
        app(StudentAccess::class)->selectChild($this->guardian(), $studentId);
    }

    public function cancelRequest(int $linkId): void
    {
        $guardian = $this->guardian();

        // Hanya permintaan milik sendiri yang masih menunggu. Permintaan yang
        // ditolak sengaja tidak bisa dihapus, supaya tidak bisa diajukan ulang
        // berkali-kali dan penolakan admin tetap berarti.
        $link = GuardianLink::query()
            ->where('guardian_id', $guardian->id)
            ->pending()
            ->whereKey($linkId)
            ->first();

        abort_unless($link instanceof GuardianLink, 404);

        $link->delete();

        session()->flash('guardian_status', 'Permintaan dibatalkan.');
    }

    public function requestLink(): void
    {
        $guardian = $this->guardian();

        $validated = $this->validate([
            'studentIdentifier' => ['required', 'string', 'max:30'],
            'studentName' => ['required', 'string', 'max:255'],
            'relationship' => ['required', Rule::in(GuardianLink::RELATIONSHIPS)],
        ], [], [
            'studentIdentifier' => 'NISN atau NIS',
            'studentName' => 'nama lengkap anak',
            'relationship' => 'hubungan dengan anak',
        ]);

        $limiterKey = 'guardian-link-request:'.$guardian->id;

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->addError('studentIdentifier', 'Terlalu banyak percobaan. Silakan coba lagi dalam satu jam.');

            return;
        }

        RateLimiter::hit($limiterKey, 3600);

        $student = StudentLookup::byIdentifier((string) $validated['studentIdentifier']);

        if (! $student || $this->normalizeName($student->name) !== $this->normalizeName((string) $validated['studentName'])) {
            $this->addError('studentIdentifier', self::MISMATCH);

            return;
        }

        $existing = GuardianLink::query()
            ->where('guardian_id', $guardian->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            $this->addError('studentIdentifier', match ($existing->status) {
                GuardianLink::APPROVED => 'Anak ini sudah terhubung dengan akun Anda.',
                GuardianLink::PENDING => 'Permintaan untuk anak ini masih menunggu persetujuan sekolah.',
                default => 'Permintaan sebelumnya untuk anak ini ditolak. Silakan hubungi admin sekolah.',
            });

            return;
        }

        GuardianLink::query()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => (string) $validated['relationship'],
            'status' => GuardianLink::PENDING,
        ]);

        $this->reset('studentIdentifier', 'studentName', 'relationship');
        session()->flash('guardian_status', 'Permintaan terkirim. Sekolah akan memastikan data Anda sebelum jadwal dan kehadiran anak bisa dilihat.');
    }

    public function render(): View
    {
        $guardian = $this->guardian();
        $access = app(StudentAccess::class);
        $children = $guardian->children()->orderBy('name')->get();
        $overview = null;

        if ($children->isNotEmpty()) {
            // Pilihan lama bisa tidak berlaku lagi, misalnya karena tautannya diputus admin.
            if (! $children->contains('id', (int) session(StudentAccess::SELECTED_CHILD, 0))) {
                $access->selectChild($guardian, (int) $children->firstOrFail()->id);
            }

            $overview = $this->overview($access->viewedStudent());
        }

        return view('livewire.guardian.guardian-home', [
            'children' => $children,
            'selectedChildId' => $overview ? $overview['student']->id : null,
            'overview' => $overview,
            'linkRequests' => $guardian->guardianLinks()
                ->with('student')
                ->where('status', '!=', GuardianLink::APPROVED)
                ->latest()
                ->get(),
            'relationships' => GuardianLink::RELATIONSHIPS,
        ]);
    }

    /**
     * Ringkasan hanya-baca untuk orang tua. Nilai dan catatan guru sengaja
     * tidak disertakan pada tahap ini.
     *
     * @return array<string, mixed>
     */
    private function overview(User $student): array
    {
        $classes = SchoolClass::query()
            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereHas('students', fn (Builder $query): Builder => $query->whereKey($student->id))
            ->get(['id', 'name']);
        $classIds = $classes->pluck('id')->all();

        $schedules = Schedule::query()
            ->with('classSubject.subject', 'classSubject.teacher')
            ->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
            ->orderBy('start_time')
            ->get();

        $semester = Semester::query()
            ->with('academicYear')
            ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->first();

        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            // Tanpa semester aktif yang mencakup hari ini, tampilkan 30 hari terakhir.
            ->whereDate('date', '>=', $semester !== null ? $semester->start_date : today()->subDays(30))
            ->whereDate('date', '<=', $semester !== null ? $semester->end_date : today())
            ->orderByDesc('date')
            ->get();

        return [
            'student' => $student,
            'className' => $classes->pluck('name')->implode(', ') ?: null,
            'todaySchedules' => $schedules->where('day', SchoolDay::today())->values(),
            'week' => collect(SchoolDay::WEEK)
                ->mapWithKeys(fn (string $day): array => [$day => $schedules->where('day', $day)->values()])
                ->filter(fn (Collection $daySchedules): bool => $daySchedules->isNotEmpty()),
            'semester' => $semester,
            'attendanceCounts' => collect(['hadir', 'izin', 'sakit', 'alpa'])
                ->mapWithKeys(fn (string $status): array => [$status => $attendance->where('status', $status)->count()])
                ->all(),
            'attendanceRecords' => $attendance->take(10),
            'assignments' => Assignment::query()
                ->with('classSubject.subject')
                ->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
                ->whereBetween('deadline', [now()->subDays(14), now()->addDays(14)])
                ->withExists(['submissions as submitted' => fn (Builder $query): Builder => $query->where('student_id', $student->id)])
                ->orderBy('deadline')
                ->limit(12)
                ->get(),
            'quizzes' => Quiz::query()
                ->with('classSubject.subject')
                ->whereHas('classSubject', fn (Builder $query): Builder => $query->whereIn('class_id', $classIds))
                ->where('close_at', '>=', now()->subDays(14))
                ->where('open_at', '<=', now()->addDays(14))
                ->withExists(['attempts as completed' => fn (Builder $query): Builder => $query->where('student_id', $student->id)->whereNotNull('submitted_at')])
                ->orderBy('close_at')
                ->limit(8)
                ->get(),
            'announcements' => Announcement::query()
                ->with('schoolClass')
                ->where(fn (Builder $query): Builder => $query
                    ->where('target', 'all')
                    ->orWhere(fn (Builder $nested): Builder => $nested->where('target', 'class')->whereIn('class_id', $classIds)))
                ->latest()
                ->limit(5)
                ->get(),
            'events' => CalendarEvent::query()->whereDate('date', '>=', today())->orderBy('date')->limit(5)->get(),
        ];
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->squish()->lower()->toString();
    }

    private function guardian(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->hasRole('ortu'), 403);

        return $user;
    }
}
