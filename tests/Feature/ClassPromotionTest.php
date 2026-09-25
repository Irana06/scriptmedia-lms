<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClassPromotion;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_promotion_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.promotion.index'))->assertOk();
        $this->actingAs(User::factory()->teacher()->create())->get(route('admin.promotion.index'))->assertForbidden();
    }

    public function test_promotion_suggests_next_level_moves_students_and_graduates_top_level(): void
    {
        [$oldYear, $newYear, $classes, $students] = $this->twoYears();

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(ClassPromotion::class)
            ->assertSet('sourceYearId', (string) $oldYear->id)
            ->assertSet('targetYearId', (string) $newYear->id)
            ->call('copyClassesToTarget');

        $new7A = SchoolClass::query()->where('academic_year_id', $newYear->id)->where('name', '7A')->firstOrFail();
        $new8A = SchoolClass::query()->where('academic_year_id', $newYear->id)->where('name', '8A')->firstOrFail();
        $new9A = SchoolClass::query()->where('academic_year_id', $newYear->id)->where('name', '9A')->firstOrFail();

        $component
            ->assertSet("classTargets.{$classes['7A']->id}", (string) $new8A->id)
            ->assertSet("classTargets.{$classes['8A']->id}", (string) $new9A->id)
            ->assertSet("classTargets.{$classes['9A']->id}", 'lulus')
            // Satu siswa 7A tinggal kelas.
            ->set("studentOverrides.{$students['7A'][1]->id}", (string) $new7A->id)
            ->call('promote')
            ->assertSee('Kenaikan kelas selesai');

        $this->assertTrue($new8A->students()->whereKey($students['7A'][0]->id)->exists());
        $this->assertTrue($new7A->students()->whereKey($students['7A'][1]->id)->exists());
        $this->assertTrue($new9A->students()->whereKey($students['8A'][0]->id)->exists());
        $this->assertNotNull($students['9A'][0]->fresh()?->graduated_at);
        // Kelas lama tetap tersimpan untuk rapor tahun lalu.
        $this->assertTrue($classes['7A']->students()->whereKey($students['7A'][0]->id)->exists());
        $this->assertTrue($newYear->fresh()?->is_active);
        $this->assertFalse($oldYear->fresh()?->is_active);
    }

    public function test_running_promotion_twice_does_not_place_a_student_in_two_classes(): void
    {
        [, $newYear, $classes, $students] = $this->twoYears();

        $this->actingAs(User::factory()->admin()->create());
        $component = Livewire::test(ClassPromotion::class)->call('copyClassesToTarget')->set('activateTarget', false);
        $component->call('promote');
        $component->call('promote');

        $placements = SchoolClass::query()->where('academic_year_id', $newYear->id)
            ->whereHas('students', fn ($query) => $query->whereKey($students['7A'][0]->id))->count();
        $this->assertSame(1, $placements);
    }

    /** @return array{AcademicYear, AcademicYear, array<string, SchoolClass>, array<string, list<User>>} */
    private function twoYears(): array
    {
        $oldYear = AcademicYear::query()->create(['year_label' => '2026/2027', 'is_active' => true]);
        $newYear = AcademicYear::query()->create(['year_label' => '2027/2028', 'is_active' => false]);
        $classes = [];
        $students = [];

        foreach (['7A', '8A', '9A'] as $name) {
            $classes[$name] = SchoolClass::query()->create(['academic_year_id' => $oldYear->id, 'name' => $name]);
            $students[$name] = User::factory()->count(2)->student(mustChangePassword: false)->create()->all();
            $classes[$name]->students()->attach(collect($students[$name])->pluck('id'));
        }

        return [$oldYear, $newYear, $classes, $students];
    }
}
