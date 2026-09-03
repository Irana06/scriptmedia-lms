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
