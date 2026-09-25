<?php

namespace Tests\Feature;

use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_uses_school_name_and_installable_icons(): void
    {
        SchoolProfile::current()->update(['name' => 'SMP Harapan Bangsa']);

        $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'SMP Harapan Bangsa')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('icons.1.sizes', '512x512');
    }

    public function test_pages_link_the_manifest_and_register_the_service_worker(): void
    {
        $this->get(route('home'))
            ->assertSee('rel="manifest"', false)
            ->assertSee('serviceWorker', false);

        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icon-512.png'));
    }
}
