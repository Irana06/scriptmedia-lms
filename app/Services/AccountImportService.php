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

                    if (isset($seen[$key])) {
                        throw new RuntimeException('Data duplikat di dalam file.');
                    }

                    $seen[$key] = true;
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
            'name' => $this->text($row['nama'] ?? null),
            'nisn' => $this->text($row['nisn'] ?? null),
            'nik' => $this->text($row['nik'] ?? null),
            'gender' => $this->normalizeGender($row['jenis_kelamin'] ?? null),
            'class' => $this->text($row['kelasrombel'] ?? $row['kelas_rombel'] ?? null),
        ];

        Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10'],
            'nik' => ['required', 'digits:16'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'class' => ['required', 'string', 'max:30'],
        ])->validate();

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

        return DB::transaction(function () use ($values, $activeYear, $schoolClass): array {
            $user = User::query()
                ->where('nisn', $values['nisn'])
                ->orWhere('username', $values['nisn'])
                ->first();

            if ($user && $user->roles()->exists() && ! $user->hasRole('siswa')) {
                throw new RuntimeException('NISN/username sudah digunakan akun non-siswa.');
            }

            $email = $values['nisn'].'@students.invalid';
            $emailOwner = User::query()->where('email', $email)->when($user, fn ($query) => $query->whereKeyNot($user->id))->exists();

            if ($emailOwner) {
                throw new RuntimeException('Email akun internal siswa sudah digunakan.');
            }

            $password = $this->password();
            $user ??= new User;
            $user->fill([
                'name' => $values['name'],
                'email' => $email,
                'username' => $values['nisn'],
                'nisn' => $values['nisn'],
                'nik' => $values['nik'],
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
            'name' => $this->text($row['nama'] ?? null),
            'email' => Str::lower($this->text($row['email'] ?? null)),
            'nip' => $this->text($row['nip'] ?? null),
        ];

        Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'nip' => ['required', 'string', 'max:30'],
        ])->validate();

        return DB::transaction(function () use ($values): array {
            $user = User::query()->where('email', $values['email'])->first();

            if ($user && $user->roles()->exists() && ! $user->hasRole('guru')) {
                throw new RuntimeException('Email sudah digunakan akun non-guru.');
            }

            $nipUsed = User::query()->where('nip', $values['nip'])->when($user, fn ($query) => $query->whereKeyNot($user->id))->exists();

            if ($nipUsed) {
                throw new RuntimeException('NIP sudah digunakan guru lain.');
            }

            $password = $this->password();
            $user ??= new User;
            $user->fill([
                'name' => $values['name'],
                'email' => $values['email'],
                'nip' => $values['nip'],
                'password' => $password,
                'must_change_password' => true,
            ]);
            $user->email_verified_at ??= Carbon::now();
            $user->save();
            $user->syncRoles([Role::findOrCreate('guru', 'web')]);

            return ['name' => $user->name, 'login' => $user->email, 'password' => $password];
        });
    }

    /** @param array<string, mixed> $row */
    private function uniqueKey(string $type, array $row): string
    {
        $value = $type === 'siswa' ? ($row['nisn'] ?? null) : ($row['email'] ?? null);

        return $type.':'.Str::lower($this->text($value));
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
