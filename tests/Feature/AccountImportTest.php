<?php

namespace Tests\Feature;

use App\Exports\AccountCredentialsExport;
use App\Exports\AccountImportTemplateExport;
use App\Livewire\Admin\AccountImport;
use App\Models\AcademicYear;
use App\Models\DataImport;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AccountImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_account_import_page(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('Import akun siswa &amp; guru', false);

        $this->actingAs($teacher)
            ->get(route('admin.imports.index'))
            ->assertForbidden();
    }

    public function test_student_import_creates_accounts_assigns_classes_and_records_invalid_rows(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);

        $contents = Excel::raw(new class implements FromArray, WithHeadings
        {
            public function headings(): array
            {
                return ['nama', 'nisn', 'nik', 'jenis_kelamin', 'kelas/rombel'];
            }

            public function array(): array
            {
                return [
                    ['Budi Santoso', '0012345678', '3273010101010001', 'L', '7A'],
                    ['Ani Lestari', '0012345679', '3273010101010002', 'Perempuan', '7A'],
                    ['Tanpa NISN', '', '3273010101010003', 'L', '7A'],
                ];
            }
        }, ExcelWriter::XLSX);

        $this->actingAs($admin);

        Livewire::test(AccountImport::class)
            ->set('type', 'siswa')
            ->set('file', UploadedFile::fake()->createWithContent('siswa.xlsx', $contents))
            ->call('import')
            ->assertHasNoErrors();

        $import = DataImport::query()->firstOrFail();

        $this->assertSame('done', $import->status);
        $this->assertSame(3, $import->total_rows);
        $this->assertSame(2, $import->success_count, json_encode($import->failures) ?: 'No failure details');
        $this->assertSame(1, $import->failed_count);
        $this->assertSame(4, $import->failures[0]['row']);
        $this->assertNotNull($import->result_file_path);
        Storage::disk('local')->assertExists($import->result_file_path);

        $student = User::query()->where('nisn', '0012345678')->firstOrFail();
        $this->assertTrue($student->hasRole('siswa'));
        $this->assertTrue($student->must_change_password);
        $this->assertSame('L', $student->gender);
        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $student->id]);
    }

    public function test_teacher_import_creates_teacher_account(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $contents = Excel::raw(new AccountImportTemplateExport('guru'), ExcelWriter::XLSX);

        $this->actingAs($admin);

        Livewire::test(AccountImport::class)
            ->set('type', 'guru')
            ->set('file', UploadedFile::fake()->createWithContent('guru.xlsx', $contents))
            ->call('import')
            ->assertHasNoErrors();

        $import = DataImport::query()->firstOrFail();
        $teacher = User::query()->where('email', 'siti.aminah@sekolah.sch.id')->first();

        $this->assertNotNull($teacher, json_encode($import->failures) ?: 'No failure details');

        $this->assertTrue($teacher->hasRole('guru'));
        $this->assertSame('198701012010012001', $teacher->nip);
        $this->assertTrue($teacher->must_change_password);
    }

    /** @param list<string> $headings */
    private function spreadsheet(array $headings, array $rows): string
    {
        return Excel::raw(new class($headings, $rows) implements FromArray, WithHeadings
        {
            public function __construct(private array $headings, private array $rows) {}

            public function headings(): array
            {
                return $this->headings;
            }

            public function array(): array
            {
                return $this->rows;
            }
        }, ExcelWriter::XLSX);
    }

    private function runImport(string $type, string $contents): DataImport
    {
        Livewire::test(AccountImport::class)
            ->set('type', $type)
            ->set('file', UploadedFile::fake()->createWithContent("{$type}.xlsx", $contents))
            ->call('import')
            ->assertHasNoErrors();

        return DataImport::query()->latest('id')->firstOrFail();
    }

    public function test_student_without_nisn_can_be_imported_using_school_number(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);

        $this->actingAs($admin);

        $import = $this->runImport('siswa', $this->spreadsheet(
            ['nama', 'nisn', 'nis', 'nik', 'jenis_kelamin', 'kelas'],
            [['Siswa Baru', '', '2024001', '', 'L', '7A']],
        ));

        $this->assertSame(1, $import->success_count, json_encode($import->failures) ?: '');

        $student = User::query()->where('nis', '2024001')->firstOrFail();

        $this->assertNull($student->nisn);
        $this->assertNull($student->nik);
        $this->assertSame('2024001', $student->username);
        $this->assertTrue($student->hasRole('siswa'));
    }

    public function test_student_row_without_any_identifier_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);

        $this->actingAs($admin);

        $import = $this->runImport('siswa', $this->spreadsheet(
            ['nama', 'nisn', 'nis', 'jenis_kelamin', 'kelas'],
            [['Tanpa Identitas', '', '', 'L', '7A']],
        ));

        $this->assertSame(0, $import->success_count);
        $this->assertSame('NISN atau NIS harus diisi.', $import->failures[0]['message']);
    }

    public function test_teacher_without_nip_can_be_imported(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $import = $this->runImport('guru', $this->spreadsheet(
            ['nama', 'email', 'nip', 'nuptk'],
            [
                ['Guru Yayasan', 'yayasan@sekolah.sch.id', '', '1234567890123456'],
                ['Guru Honorer', 'honorer@sekolah.sch.id', '', ''],
            ],
        ));

        $this->assertSame(2, $import->success_count, json_encode($import->failures) ?: '');

        $yayasan = User::query()->where('email', 'yayasan@sekolah.sch.id')->firstOrFail();
        $honorer = User::query()->where('email', 'honorer@sekolah.sch.id')->firstOrFail();

        $this->assertNull($yayasan->nip);
        $this->assertSame('1234567890123456', $yayasan->nuptk);
        $this->assertNull($honorer->nip);
        $this->assertNull($honorer->nuptk);
        $this->assertTrue($honorer->hasRole('guru'));
    }

    public function test_duplicate_nuptk_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $import = $this->runImport('guru', $this->spreadsheet(
            ['nama', 'email', 'nuptk'],
            [
                ['Guru Satu', 'satu@sekolah.sch.id', '1111111111111111'],
                ['Guru Dua', 'dua@sekolah.sch.id', '1111111111111111'],
            ],
        ));

        $this->assertSame(1, $import->success_count);
        $this->assertSame('NUPTK sudah digunakan guru lain.', $import->failures[0]['message']);

        // Baris kedua tidak boleh mengambil alih akun guru pertama.
        $first = User::query()->where('nuptk', '1111111111111111')->firstOrFail();
        $this->assertSame('satu@sekolah.sch.id', $first->email);
        $this->assertSame('Guru Satu', $first->name);
        $this->assertNull(User::query()->where('email', 'dua@sekolah.sch.id')->first());
    }

    public function test_teacher_first_imported_without_email_is_updated_when_their_email_is_added(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'email' => 'rina.lestari@guru.invalid',
            'username' => 'rina.lestari',
            'nuptk' => '2222222222222222',
        ]);

        $this->actingAs($admin);

        $import = $this->runImport('guru', $this->spreadsheet(
            ['nama', 'email', 'nuptk'],
            [['Rina Lestari', 'rina@sekolah.sch.id', '2222222222222222']],
        ));

        $this->assertSame(1, $import->success_count, json_encode($import->failures) ?: '');
        $this->assertSame(2, User::query()->count());

        $teacher->refresh();

        $this->assertSame('rina@sekolah.sch.id', $teacher->email);
        $this->assertSame('rina.lestari', $teacher->username);
    }

    public function test_alternative_column_names_from_dapodik_exports_are_accepted(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A']);

        $this->actingAs($admin);

        $import = $this->runImport('siswa', $this->spreadsheet(
            ['nama_peserta_didik', 'nisn', 'jk', 'rombel'],
            [['Budi Santoso', '0012345678', 'Laki-laki', '7A']],
        ));

        $this->assertSame(1, $import->success_count, json_encode($import->failures) ?: '');
        $this->assertSame('L', User::query()->where('nisn', '0012345678')->firstOrFail()->gender);
    }

    public function test_teacher_without_email_gets_a_username_from_their_name(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $import = $this->runImport('guru', $this->spreadsheet(
            ['nama', 'email', 'nip', 'nuptk'],
            [
                ['Rina Lestari, S.Pd.', '', '', ''],
                ['Rina Lestari', '', '', ''],
            ],
        ));

        $this->assertSame(2, $import->success_count, json_encode($import->failures) ?: '');

        $first = User::query()->where('username', 'rina.lestari')->firstOrFail();
        $second = User::query()->where('username', 'rina.lestari2')->firstOrFail();

        $this->assertSame('rina.lestari@guru.invalid', $first->email);
        $this->assertSame('Rina Lestari, S.Pd.', $first->name);
        $this->assertNotNull($first->email_verified_at);
        $this->assertTrue($first->hasRole('guru'));
        $this->assertTrue($second->hasRole('guru'));
    }

    public function test_reimporting_a_teacher_by_nuptk_without_email_keeps_their_existing_email(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create([
            'email' => 'yuli@sekolah.sch.id',
            'nuptk' => '7845762663300012',
        ]);

        $this->actingAs($admin);

        $import = $this->runImport('guru', $this->spreadsheet(
            ['nama', 'email', 'nuptk'],
            [['Yuli Astuti', '', '7845762663300012']],
        ));

        $this->assertSame(1, $import->success_count, json_encode($import->failures) ?: '');
        $this->assertSame(2, User::query()->count());

        $teacher->refresh();

        $this->assertSame('yuli@sekolah.sch.id', $teacher->email);
        $this->assertSame('Yuli Astuti', $teacher->name);
        $this->assertNull($teacher->username);
    }

    public function test_credentials_file_can_only_be_downloaded_once(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $path = 'imports/results/accounts.xlsx';

        Excel::store(
            new AccountCredentialsExport([['name' => 'Budi', 'login' => '0012345678', 'password' => 'Secret123']]),
            $path,
            'local',
            ExcelWriter::XLSX,
        );

        $import = DataImport::query()->create([
            'type' => 'siswa',
            'file_path' => 'imports/source/source.xlsx',
            'imported_by' => $admin->id,
            'total_rows' => 1,
            'success_count' => 1,
            'status' => 'done',
            'result_file_path' => $path,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.imports.credentials', $import))
            ->assertOk()
            ->assertDownload("kartu-akun-siswa-{$import->id}.xlsx");

        $this->assertNotNull($import->refresh()->downloaded_at);

        $this->actingAs($admin)
            ->get(route('admin.imports.credentials', $import))
            ->assertGone();
    }
}
