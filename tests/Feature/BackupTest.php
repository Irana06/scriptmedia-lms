<?php

namespace Tests\Feature;

use App\Livewire\Admin\Backups;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    // Bukan RefreshDatabase: pemulihan mematikan cek foreign key, yang di SQLite
    // tidak berlaku di dalam transaksi pembungkus tes.
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_backup_zip_contains_manifest_and_tables_but_not_sessions(): void
    {
        User::factory()->admin()->create(['name' => 'Admin Cadangan']);

        $name = app(BackupService::class)->create();
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path("backups/{$name}"));

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $this->assertSame('RuangKelas', $manifest['app']);
        $this->assertContains('users', $manifest['tables']);
        $this->assertNotContains('sessions', $manifest['tables']);
        $this->assertStringContainsString('Admin Cadangan', (string) $zip->getFromName('tables/users.jsonl'));
    }

    public function test_restore_brings_back_data_exactly(): void
    {
        $admin = User::factory()->admin()->create();
        $year = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $class = SchoolClass::query()->create(['academic_year_id' => $year->id, 'name' => '7A', 'homeroom_teacher_id' => $admin->id]);
        SchoolProfile::current()->update(['name' => 'SMP Asli']);
        Storage::disk('public')->put('branding/logo.png', 'logo-asli');

        $backups = app(BackupService::class);
        $name = $backups->create(withFiles: true);

        // Kejadian buruk: data berubah dan terhapus.
        $class->delete();
        SchoolProfile::query()->whereKey(1)->update(['name' => 'Rusak']);
        User::factory()->count(3)->create();
        Storage::disk('public')->delete('branding/logo.png');

        $this->artisan('sekolah:restore', ['berkas' => $name, '--force' => true])->assertExitCode(0);

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'name' => '7A', 'homeroom_teacher_id' => $admin->id]);
        $this->assertDatabaseHas('school_profiles', ['id' => 1, 'name' => 'SMP Asli']);
        $this->assertSame(1, User::query()->count());
        $this->assertTrue($admin->fresh()?->hasRole('admin'));
        Storage::disk('public')->assertExists('branding/logo.png');
    }

    public function test_admin_page_creates_lists_and_downloads_backups(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Backups::class)
            ->call('createBackup')
            ->assertSee('berhasil dibuat')
            ->assertSee('Data saja');

        $name = app(BackupService::class)->list()[0]['name'];
        $this->get(route('admin.backups.download', $name))->assertOk()->assertDownload($name);
        $this->get(route('admin.backups.download', '..%2F..%2F.env'))->assertNotFound();

        $this->actingAs(User::factory()->teacher()->create())->get(route('admin.backups.index'))->assertForbidden();
    }

    public function test_prune_keeps_daily_and_full_backups_separately(): void
    {
        $backups = app(BackupService::class);
        foreach (['2026-09-01-013000', '2026-09-02-013000', '2026-09-03-013000'] as $stamp) {
            Storage::disk('local')->put("backups/cadangan-{$stamp}.zip", 'x');
        }
        Storage::disk('local')->put('backups/cadangan-2026-08-30-023000-lengkap.zip', 'x');

        $this->assertSame(2, $backups->prune(1));
        $this->assertSame(['cadangan-2026-08-30-023000-lengkap.zip'], array_values(array_filter(
            array_column($backups->list(), 'name'),
            fn (string $name): bool => str_ends_with($name, 'lengkap.zip'),
        )));
    }
}
