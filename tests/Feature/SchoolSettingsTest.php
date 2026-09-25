<?php

namespace Tests\Feature;

use App\Livewire\Admin\SchoolSettings;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_school_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.school.index'))
            ->assertOk()
            ->assertSee('Profil sekolah');

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('admin.school.index'))
            ->assertForbidden();
    }

    public function test_admin_can_save_profile_and_upload_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SchoolSettings::class)
            ->set('name', 'SMP Harapan Bangsa')
            ->set('city', 'Bandung')
            ->set('principalName', 'Dra. Siti Rahma')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
            ->call('save')
            ->assertHasNoErrors();

        $profile = SchoolProfile::query()->findOrFail(1);
        $this->assertSame('SMP Harapan Bangsa', $profile->name);
        $this->assertSame('Dra. Siti Rahma', $profile->principal_name);
        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists((string) $profile->logo_path);
        $this->assertStringStartsWith('data:image/png;base64,', (string) $profile->logoDataUri());
    }

    public function test_logo_must_be_png_or_jpg(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SchoolSettings::class)
            ->set('name', 'SMP Harapan Bangsa')
            ->set('logo', UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'))
            ->call('save')
            ->assertHasErrors('logo');
    }

    public function test_login_pages_show_the_configured_school_name(): void
    {
        SchoolProfile::current()->update(['name' => 'SMP Harapan Bangsa']);

        $this->get(route('login'))->assertSee('SMP Harapan Bangsa');
        $this->get(route('siswa.login'))->assertSee('SMP Harapan Bangsa');
        $this->get(route('home'))->assertSee('SMP Harapan Bangsa');
    }

    public function test_report_card_renders_with_school_letterhead(): void
    {
        SchoolProfile::current()->update([
            'name' => 'SMP Harapan Bangsa',
            'city' => 'Bandung',
            'principal_name' => 'Dra. Siti Rahma',
            'principal_nip' => '197001012000032001',
        ]);

        $html = view('pdf.report-card', [
            'school' => SchoolProfile::current(),
            'student' => User::factory()->student()->make(['name' => 'Budi', 'nisn' => '0099000001']),
            'semester' => new Semester(['name' => 'Ganjil']),
            'schoolClass' => tap(new SchoolClass(['name' => '7A']), fn ($class) => $class->setRelation('academicYear', new AcademicYear(['year_label' => '2026/2027']))->setRelation('homeroomTeacher', null)),
            'grades' => collect(),
            'attendanceCounts' => collect(['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0]),
        ])->render();

        $this->assertStringContainsString('SMP Harapan Bangsa', $html);
        $this->assertStringContainsString('Dra. Siti Rahma', $html);
        $this->assertStringContainsString('NIP 197001012000032001', $html);
        $this->assertStringContainsString('Bandung,', $html);
    }
}
