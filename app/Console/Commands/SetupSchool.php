<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

/**
 * Wizard sekali-jalan untuk instalasi baru: profil sekolah, akun admin
 * pertama, dan tahun ajaran aktif. Setelah ini admin login lewat browser
 * dan melengkapi sisanya (kelas, mapel, guru, siswa) di Struktur Akademik.
 *
 * Aman dijalankan berulang: baris yang sudah ada ditanya dulu sebelum
 * ditimpa, tidak pernah menggandakan sekolah atau tahun ajaran aktif.
 */
class SetupSchool extends Command
{
    protected $signature = 'sekolah:setup
        {--nama= : Nama sekolah}
        {--npsn= : NPSN sekolah, opsional}
        {--admin-nama= : Nama lengkap admin pertama}
        {--admin-email= : Email admin pertama}
        {--tahun-ajaran= : Label tahun ajaran aktif, misalnya 2026/2027}';

    protected $description = 'Setup awal sekolah baru: profil sekolah, akun admin pertama, dan tahun ajaran aktif';

    public function handle(): int
    {
        // Nonaktifkan lewat --no-interaction, mis. saat dipanggil dari skrip deploy.
        $interactive = $this->input->isInteractive();

        foreach (['admin', 'guru', 'siswa', 'ortu'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->setupSchoolProfile($interactive);
        $admin = $this->setupFirstAdmin($interactive);
        $this->setupActiveAcademicYear($interactive);

        note('Setup selesai. Langkah berikutnya: login sebagai admin, lalu lengkapi Struktur Akademik (semester, kelas, mata pelajaran, guru) dan Bobot Nilai.');

        if ($admin !== null) {
            table(['Kolom', 'Nilai'], [
                ['Nama', $admin['name']],
                ['Masuk dengan', $admin['login']],
                ['Password sementara', $admin['password'] ?? '(tidak berubah, akun sudah ada)'],
            ]);

            if ($admin['password'] !== null) {
                $this->warn('Catat password di atas sekarang — tidak akan ditampilkan lagi. Admin wajib menggantinya saat login pertama.');
            }
        }

        return self::SUCCESS;
    }

    private function setupSchoolProfile(bool $interactive): void
    {
        $profile = SchoolProfile::current();

        $name = $this->option('nama');

        if ($name === null && $interactive) {
            $name = text(
                label: 'Nama sekolah',
                default: $profile->name === 'Sekolah Baru' ? '' : $profile->name,
                required: true,
            );
        }

        $npsn = $this->option('npsn');

        if ($npsn === null && $interactive) {
            $npsn = text(label: 'NPSN (boleh dikosongkan)', default: $profile->npsn ?? '');
        }

        if ($name === null && $npsn === null) {
            return;
        }

        $profile->update(array_filter([
            'name' => $name !== '' ? $name : null,
            'npsn' => $npsn !== '' ? $npsn : $profile->npsn,
        ], fn ($value): bool => $value !== null));

        $this->info("Profil sekolah: {$profile->fresh()->name}");
    }

    /** @return array{name: string, login: string, password: string|null}|null */
    private function setupFirstAdmin(bool $interactive): ?array
    {
        if (User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->exists()) {
            $shouldAddAnother = $interactive
                ? confirm('Sudah ada akun admin. Tambah admin baru lagi?', default: false)
                : false;

            if (! $shouldAddAnother) {
                $this->info('Sudah ada akun admin, dilewati.');

                return null;
            }
        }

        $name = $this->option('admin-nama') ?: ($interactive ? text('Nama lengkap admin', required: true) : null);
        $email = $this->option('admin-email') ?: ($interactive ? text(
            label: 'Email admin',
            required: true,
            validate: fn (string $value): ?string => Validator::make(
                ['email' => $value],
                ['email' => ['email:rfc', 'unique:users,email']],
            )->fails() ? 'Email tidak valid atau sudah dipakai.' : null,
        ) : null);

        if ($name === null || $email === null) {
            $this->warn('Nama atau email admin tidak diisi, akun admin dilewati. Jalankan lagi dengan --admin-nama dan --admin-email.');

            return null;
        }

        $password = Str::password(14, symbols: false);

        $admin = DB::transaction(function () use ($name, $email, $password): User {
            $admin = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
                'must_change_password' => true,
            ]);
            $admin->assignRole('admin');

            return $admin;
        });

        return ['name' => $admin->name, 'login' => $admin->email, 'password' => $password];
    }

    private function setupActiveAcademicYear(bool $interactive): void
    {
        if (AcademicYear::query()->where('is_active', true)->exists()) {
            $this->info('Sudah ada tahun ajaran aktif, dilewati.');

            return;
        }

        $label = $this->option('tahun-ajaran')
            ?: ($interactive ? text('Label tahun ajaran aktif, misalnya 2026/2027', required: true) : null);

        if ($label === null) {
            $this->warn('Tahun ajaran belum dibuat. Tambahkan nanti di Struktur Akademik, atau jalankan lagi dengan --tahun-ajaran.');

            return;
        }

        AcademicYear::query()->updateOrCreate(['year_label' => $label], ['is_active' => true]);
        $this->info("Tahun ajaran aktif: {$label}");
    }
}
