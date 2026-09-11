<?php

namespace App\Livewire\Admin;

use App\Actions\ResetAccountPassword;
use App\Models\GuardianLink;
use App\Models\User;
use App\Support\StudentLookup;
use App\Support\UsernameGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.guru-admin')]
#[Title('Orang Tua & Wali')]
class GuardianManager extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $studentIdentifier = '';

    public string $relationship = '';

    /** @var array{name: string, login: string, password: string}|null */
    public ?array $createdCredentials = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function approve(int $linkId): void
    {
        $this->review($linkId, GuardianLink::APPROVED, 'disetujui');
    }

    public function reject(int $linkId): void
    {
        $this->review($linkId, GuardianLink::REJECTED, 'ditolak');
    }

    public function revoke(int $linkId): void
    {
        $this->authorizeAdmin();

        $link = GuardianLink::query()->approved()->with('guardian', 'student')->findOrFail($linkId);
        $link->delete();

        // StudentAccess memeriksa tautan di setiap request, jadi akses berhenti
        // saat itu juga, termasuk untuk sesi orang tua yang masih terbuka.
        session()->flash('guardian_admin_status', "Tautan {$link->guardian->name} ke {$link->student->name} diputus.");
    }

    public function resetPassword(int $guardianId): void
    {
        $admin = $this->authorizeAdmin();
        $this->createdCredentials = null;

        // Bukan User::role('ortu'): scope itu melempar exception bila peran ortu
        // belum pernah dibuat, misalnya di server lama sebelum ada orang tua.
        $guardian = User::query()->findOrFail($guardianId);
        abort_unless($guardian->hasRole('ortu'), 404);
        $password = app(ResetAccountPassword::class)->handle($guardian, $admin);

        // Orang tua yang ditambahkan tanpa email tidak bisa memakai tautan lupa
        // password, jadi admin yang menyampaikan password baru secara langsung.
        $this->createdCredentials = [
            'name' => $guardian->name,
            'login' => $guardian->username ?? $guardian->email,
            'password' => $password,
        ];

        session()->flash('guardian_admin_status', "Password {$guardian->name} direset. Password baru hanya ditampilkan sekali.");
    }

    public function createGuardian(): void
    {
        $admin = $this->authorizeAdmin();
        $this->createdCredentials = null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'studentIdentifier' => ['required', 'string', 'max:30'],
            'relationship' => ['required', Rule::in(GuardianLink::RELATIONSHIPS)],
        ], [], [
            'name' => 'nama',
            'studentIdentifier' => 'NISN atau NIS',
            'relationship' => 'hubungan',
        ]);

        $student = StudentLookup::byIdentifier((string) $validated['studentIdentifier']);

        if (! $student) {
            $this->addError('studentIdentifier', 'Siswa dengan NISN atau NIS tersebut tidak ditemukan.');

            return;
        }

        $email = Str::lower(trim((string) $validated['email']));
        $existing = $email !== '' ? User::query()->where('email', $email)->first() : null;

        if ($existing && ! $existing->hasRole('ortu')) {
            $this->addError('email', 'Email ini sudah dipakai akun yang bukan orang tua.');

            return;
        }

        $credentials = DB::transaction(function () use ($validated, $email, $existing, $student, $admin): ?array {
            $guardian = $existing;
            $credentials = null;

            if (! $guardian) {
                $password = Str::password(12, symbols: false);
                $username = $email === '' ? UsernameGenerator::fromName((string) $validated['name'], 'ortu.invalid') : null;

                $guardian = new User;
                $guardian->fill([
                    'name' => (string) $validated['name'],
                    'email' => $email !== '' ? $email : $username.'@ortu.invalid',
                    'username' => $username,
                    'phone' => ((string) $validated['phone']) !== '' ? (string) $validated['phone'] : null,
                    'password' => $password,
                    'must_change_password' => true,
                ]);
                $guardian->email_verified_at = Carbon::now();
                $guardian->save();
                $guardian->assignRole(Role::findOrCreate('ortu', 'web'));

                $credentials = ['name' => $guardian->name, 'login' => $username ?? $email, 'password' => $password];
            }

            // Tautan yang dibuat sekolah langsung disetujui: admin sudah memastikan
            // hubungan wali secara langsung.
            GuardianLink::query()->updateOrCreate(
                ['guardian_id' => $guardian->id, 'student_id' => $student->id],
                [
                    'relationship' => (string) $validated['relationship'],
                    'status' => GuardianLink::APPROVED,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ],
            );

            return $credentials;
        });

        $this->reset('name', 'email', 'phone', 'studentIdentifier', 'relationship');
        $this->createdCredentials = $credentials;

        session()->flash('guardian_admin_status', $credentials
            ? "Akun orang tua dibuat dan langsung terhubung dengan {$student->name}."
            : "Akun orang tua yang sudah ada kini terhubung dengan {$student->name}.");
    }

    public function render(): View
    {
        return view('livewire.admin.guardian-manager', [
            'pending' => GuardianLink::query()->pending()->with('guardian', 'student.schoolClasses')->oldest()->get(),
            'approved' => GuardianLink::query()->approved()->with('guardian', 'student.schoolClasses')->latest('reviewed_at')->limit(50)->get(),
            'relationships' => GuardianLink::RELATIONSHIPS,
        ]);
    }

    private function review(int $linkId, string $status, string $verb): void
    {
        $admin = $this->authorizeAdmin();

        $link = GuardianLink::query()->pending()->with('guardian', 'student')->findOrFail($linkId);
        $link->update([
            'status' => $status,
            'reviewed_by' => $admin->id,
            'reviewed_at' => Carbon::now(),
        ]);

        session()->flash('guardian_admin_status', "Permintaan {$link->guardian->name} untuk {$link->student->name} {$verb}.");
    }

    private function authorizeAdmin(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403);

        return $user;
    }
}
