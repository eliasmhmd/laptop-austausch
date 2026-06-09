<?php

namespace Tests\Feature\Booking;

use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftwareCatalogResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): SoftwareCatalogResolver
    {
        return app(SoftwareCatalogResolver::class);
    }

    public function test_resolve_reuses_an_existing_entry_case_insensitively(): void
    {
        $office = SoftwareCatalog::factory()->create(['name' => 'Microsoft Office']);

        $resolved = $this->resolver()->resolve('  microsoft OFFICE ');

        $this->assertTrue($resolved->is($office));
        $this->assertSame(1, SoftwareCatalog::count());
    }

    public function test_resolve_creates_a_new_non_standard_entry(): void
    {
        $resolved = $this->resolver()->resolve('Fachverfahren IBX');

        $this->assertSame('Fachverfahren IBX', $resolved->name);
        $this->assertFalse($resolved->is_standard);
        $this->assertDatabaseHas('software_catalog', ['name' => 'Fachverfahren IBX', 'is_standard' => false]);
    }

    public function test_normalize_trims_filters_and_dedupes_case_insensitively(): void
    {
        $names = $this->resolver()->normalizeNames(['  Office ', 'office', '', '   ', 'GIMP']);

        // Erste Schreibweise gewinnt, Dubletten/Leereinträge entfallen.
        $this->assertSame(['Office', 'GIMP'], $names);
    }
}
