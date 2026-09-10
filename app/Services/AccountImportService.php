<?php

namespace App\Services;

use App\Exports\AccountCredentialsExport;
use App\Imports\SpreadsheetRowsImport;
use App\Models\AcademicYear;
use App\Models\DataImport;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class AccountImportService
{
    public function process(DataImport $dataImport): void
    {
        $reader = new SpreadsheetRowsImport;

        try {
            Excel::import($reader, $dataImport->file_path, 'local');

            $credentials = [];
            $failures = [];
            $seen = [];

            foreach ($reader->rows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $key = $this->uniqueKey($dataImport->type, $row);

                    if ($key !== null && isset($seen[$key])) {
                        throw new RuntimeException('Data duplikat di dalam file.');
                    }

                    if ($key !== null) {
                        $seen[$key] = true;
                    }

                    $credentials[] = $dataImport->type === 'siswa'
                        ? $this->importStudent($row)
                        : $this->importTeacher($row);
                } catch (Throwable $exception) {
                    $failures[] = [
                        'row' => $rowNumber,
                        'message' => $this->failureMessage($exception),
                    ];
                }
            }

            $resultPath = null;

            if ($credentials !== []) {
                $resultPath = 'imports/results/'.$dataImport->id.'-'.Str::uuid().'.xlsx';
                Excel::store(new AccountCredentialsExport($credentials), $resultPath, 'local', ExcelWriter::XLSX);
            }

            $dataImport->update([
                'total_rows' => count($reader->rows),
                'success_count' => count($credentials),
                'failed_count' => count($failures),
                'failures' => $failures,
                'result_file_path' => $resultPath,
                'status' => 'done',
            ]);
        } catch (Throwable $exception) {
            $dataImport->update(['status' => 'failed']);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, login: string, password: string}
     */
    private function importStudent(array $row): array
    {
        $values = [
            'name' => $this->pick($row, 'nama', 'nama_lengkap', 'nama_siswa', 'nama_peserta_didik'),
            'nisn' => $this->pick($row, 'nisn'),
            'nis' => $this->pick($row, 'nis', 'no_induk', 'nomor_induk', 'no_induk_siswa'),
            'nik' => $this->pick($row, 'nik', 'nik_siswa', 'no_ktp'),
            'gender' => $this->normalizeGender($this->pick($row, 'jenis_kelamin', 'jk', 'gender', 'lp')),
            'class' => $this->pick($row, 'kelasrombel', 'kelas_rombel', 'kelas', 'rombel', 'nama_rombel', 'rombongan_belajar'),
        ];

        Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            // NISN terbit lewat Dapodik dan sering belum ada untuk siswa baru,
            // jadi nomor induk sekolah boleh menggantikannya sebagai identitas masuk.
            'nisn' => ['nullable', 'required_without:nis', 'digits:10'],
            'nis' => ['nullable', 'required_without:nisn', 'string', 'max:30'],
            'nik' => ['nullable', 'digits:16'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'class' => ['required', 'string', 'max:30'],
        ], [
            'nisn.required_without' => 'NISN atau NIS harus diisi.',
            'nis.required_without' => 'NISN atau NIS harus diisi.',
        ])->validate();

        $login = $values['nisn'] !== '' ? $values['nisn'] : $values['nis'];

        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        if (! $activeYear) {
            throw new RuntimeException('Belum ada tahun ajaran aktif.');
        }

        $schoolClass = SchoolClass::query()
            ->where('academic_year_id', $activeYear->id)
            ->where('name', $values['class'])
            ->first();

        if (! $schoolClass) {
            throw new RuntimeException("Kelas {$values['class']} tidak ditemukan pada tahun ajaran aktif.");
        }

        return DB::transaction(function () use ($values, $login, $activeYear, $schoolClass): array {
            $user = User::query()
                ->where(function ($query) use ($login): void {
                    $query->where('username', $login)->orWhere('nisn', $login)->orWhere('nis', $login);
                })
                ->first();

            if ($user && $user->roles()->exists() && ! $user->hasRole('siswa')) {
                throw new RuntimeException('NISN/NIS sudah digunakan akun non-siswa.');
            }

            $email = $login.'@students.invalid';
            $emailOwner = User::query()->where('email', $email)->when($user, fn ($query) => $query->whereKeyNot($user->id))->exists();

            if ($emailOwner) {
                throw new RuntimeException('Email akun internal siswa sudah digunakan.');
            }

            $password = $this->password();
            $user ??= new User;
            $user->fill([
                'name' => $values['name'],
                'email' => $email,
                'username' => $login,
                'nisn' => $values['nisn'] !== '' ? $values['nisn'] : null,
                'nis' => $values['nis'] !== '' ? $values['nis'] : null,
                'nik' => $values['nik'] !== '' ? $values['nik'] : null,
                'gender' => $values['gender'],
                'password' => $password,
                'must_change_password' => true,
            ])->save();
            $user->syncRoles([Role::findOrCreate('siswa', 'web')]);

            DB::table('class_students')
                ->where('student_id', $user->id)
                ->whereIn('class_id', $activeYear->classes()->select('classes.id'))
                ->delete();
            $schoolClass->students()->syncWithoutDetaching([$user->id]);

            return ['name' => $user->name, 'login' => $user->username ?? '', 'password' => $password];
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, login: string, password: string}
     */
    private function importTeacher(array $row): array
    {
        $values = [
            'name' => $this->pick($row, 'nama', 'nama_lengkap', 'nama_guru', 'nama_ptk'),
            'email' => Str::lower($this->pick($row, 'email', 'surel', 'alamat_email')),
            'nip' => $this->pick($row, 'nip'),
            'nuptk' => $this->pick($row, 'nuptk'),
        ];

        Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            // Banyak guru di sekolah swasta tidak memiliki email aktif. Tanpa email,
            // akun dibuatkan username dari nama yang tercetak pada kartu akun.
            'email' => ['nullable', 'email:rfc', 'max:255'],
            // NIP hanya dimiliki ASN. Guru yayasan dan honorer di sekolah swasta
            // umumnya hanya punya NUPTK, atau belum punya keduanya.
            'nip' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:30'],
        ])->validate();

        return DB::transaction(function () use ($values): array {
            $user = $this->findExistingTeacher($values);

            if ($user && $user->roles()->exists() && ! $user->hasRole('guru')) {
                throw new RuntimeException($values['email'] !== '' && $user->email === $values['email']
                    ? 'Email sudah digunakan akun non-guru.'
                    : 'NIP atau NUPTK sudah digunakan akun non-guru.');
            }

            foreach (['nip' => 'NIP', 'nuptk' => 'NUPTK'] as $field => $label) {
                if ($values[$field] === '') {
                    continue;
                }

                $used = User::query()
                    ->where($field, $values[$field])
                    ->when($user, fn ($query) => $query->whereKeyNot($user->id))
                    ->exists();

                if ($used) {
                    throw new RuntimeException("{$label} sudah digunakan guru lain.");
                }
            }

            $email = $values['email'];
            $username = $user?->username;

            if ($email === '') {
                if ($user && ! Str::endsWith($user->email, '.invalid')) {
                    // Guru yang sudah terdaftar dengan email sungguhan tetap memakainya.
                    $email = $user->email;
                } else {
                    $username ??= $this->generateTeacherUsername($values['name']);
                    $email = $username.'@guru.invalid';
                }
            }

            $emailOwner = User::query()
                ->where('email', $email)
                ->when($user, fn ($query) => $query->whereKeyNot($user->id))
                ->exists();

            if ($emailOwner) {
                throw new RuntimeException('Email sudah digunakan akun lain.');
            }

            $password = $this->password();
            $user ??= new User;
            $user->fill([
                'name' => $values['name'],
                'email' => $email,
                'username' => $username,
                'nip' => $values['nip'] !== '' ? $values['nip'] : null,
                'nuptk' => $values['nuptk'] !== '' ? $values['nuptk'] : null,
                'password' => $password,
                'must_change_password' => true,
            ]);
            $user->email_verified_at ??= Carbon::now();
            $user->save();
            $user->syncRoles([Role::findOrCreate('guru', 'web')]);

            return ['name' => $user->name, 'login' => $user->username ?? $user->email, 'password' => $password];
        });
    }

    /**
     * Cari guru yang sudah terdaftar agar impor ulang memperbarui akun, bukan
     * menggandakannya. Email dicoba lebih dulu, lalu NUPTK dan NIP — sehingga
     * guru yang dulu diimpor tanpa email tetap dikenali saat emailnya ditambahkan.
     *
     * @param  array{name: string, email: string, nip: string, nuptk: string}  $values
     */
    private function findExistingTeacher(array $values): ?User
    {
        if ($values['email'] !== '') {
            $user = User::query()->where('email', $values['email'])->first();

            if ($user) {
                return $user;
            }
        }

        foreach (['nuptk', 'nip'] as $field) {
            if ($values[$field] === '') {
                continue;
            }

            $user = User::query()->where($field, $values[$field])->first();

            if (! $user) {
                continue;
            }

            // Baris yang membawa email hanya boleh mencocokkan akun yang dulu
            // diimpor tanpa email. Akun dengan email sungguhan yang berbeda adalah
            // orang lain — kemungkinan besar NUPTK/NIP-nya salah ketik — dan tidak
            // boleh ditimpa. Pemeriksaan keunikan setelahnya akan menolak baris ini.
            if ($values['email'] !== '' && ! Str::endsWith($user->email, '.invalid')) {
                return null;
            }

            return $user;
        }

        return null;
    }

    /**
     * Username untuk guru tanpa email, dibuat dari nama agar mudah diketik di HP.
     * NIP dan NUPTK tetap tersimpan sebagai identitas, bukan kredensial masuk —
     * mengetik 18 digit angka setiap kali masuk terlalu mudah salah.
     */
    private function generateTeacherUsername(string $name): string
    {
        // Nama dari Dapodik sering membawa gelar setelah koma: "Siti Aminah, S.Pd."
        $base = Str::slug(Str::before($name, ','), '.');
        $base = $base !== '' ? $base : 'guru';
        $candidate = $base;
        $suffix = 2;

        while (User::query()->where('username', $candidate)->orWhere('email', $candidate.'@guru.invalid')->exists()) {
            $candidate = $base.$suffix++;
        }

        return $candidate;
    }

    /**
     * Kunci untuk mendeteksi baris ganda di dalam satu berkas. Baris tanpa
     * identitas apa pun tidak bisa dibedakan dari orang lain bernama sama,
     * jadi tidak diperlakukan sebagai duplikat.
     *
     * @param  array<string, mixed>  $row
     */
    private function uniqueKey(string $type, array $row): ?string
    {
        $value = $type === 'siswa'
            ? $this->pick($row, 'nisn') ?: $this->pick($row, 'nis', 'no_induk', 'nomor_induk', 'no_induk_siswa')
            : ($this->pick($row, 'email', 'surel', 'alamat_email') ?: $this->pick($row, 'nuptk') ?: $this->pick($row, 'nip'));

        return $value === '' ? null : $type.':'.Str::lower($value);
    }

    /**
     * Ambil nilai kolom pertama yang terisi.
     *
     * Ekspor Dapodik dan EMIS memakai nama kolom yang berbeda-beda, dan
     * operator sekolah jarang merapikannya sebelum mengunggah. Menerima
     * beberapa nama untuk kolom yang sama jauh lebih murah daripada meminta
     * setiap sekolah menyesuaikan berkasnya dengan template kita.
     *
     * @param  array<string, mixed>  $row
     */
    private function pick(array $row, string ...$aliases): string
    {
        foreach ($aliases as $alias) {
            $value = $this->text($row[$alias] ?? null);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function text(mixed $value): string
    {
        return trim(is_scalar($value) ? (string) $value : '');
    }

    private function normalizeGender(mixed $value): string
    {
        return match (Str::lower($this->text($value))) {
            'l', 'laki-laki', 'laki laki', 'pria' => 'L',
            'p', 'perempuan', 'wanita' => 'P',
            default => '',
        };
    }

    private function password(): string
    {
        return Str::password(12, symbols: false);
    }

    private function failureMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return $exception->validator->errors()->first();
        }

        return $exception->getMessage() ?: 'Baris tidak dapat diproses.';
    }
}
