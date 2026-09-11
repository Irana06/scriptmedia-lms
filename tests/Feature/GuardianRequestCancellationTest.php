<?php

namespace Tests\Feature;

use App\Livewire\Guardian\GuardianHome;
use App\Models\GuardianLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuardianRequestCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function link(User $guardian, string $status): GuardianLink
    {
        $student = User::factory()->student(mustChangePassword: false)->create();

        return GuardianLink::query()->create([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'ayah',
            'status' => $status,
        ]);
    }

    public function test_parent_can_cancel_their_own_pending_request(): void
    {
        $guardian = User::factory()->guardian()->create();
        $link = $this->link($guardian, GuardianLink::PENDING);

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->assertSee('Batalkan')
            ->call('cancelRequest', $link->id)
            ->assertHasNoErrors()
            ->assertSee('Permintaan dibatalkan.');

        $this->assertDatabaseMissing('guardian_student', ['id' => $link->id]);
    }

    public function test_parent_cannot_cancel_another_parents_request(): void
    {
        $guardian = User::factory()->guardian()->create();
        $link = $this->link(User::factory()->guardian()->create(), GuardianLink::PENDING);

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->call('cancelRequest', $link->id)
            ->assertNotFound();

        $this->assertDatabaseHas('guardian_student', ['id' => $link->id]);
    }

    public function test_approved_and_rejected_links_cannot_be_removed_by_the_parent(): void
    {
        $guardian = User::factory()->guardian()->create();
        $approved = $this->link($guardian, GuardianLink::APPROVED);
        $rejected = $this->link($guardian, GuardianLink::REJECTED);

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->assertDontSee('Batalkan')
            ->call('cancelRequest', $approved->id)
            ->assertNotFound();

        Livewire::actingAs($guardian)
            ->test(GuardianHome::class)
            ->call('cancelRequest', $rejected->id)
            ->assertNotFound();

        $this->assertDatabaseCount('guardian_student', 2);
    }
}
