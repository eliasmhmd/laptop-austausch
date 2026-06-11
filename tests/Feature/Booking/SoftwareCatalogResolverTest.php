<?php

namespace Tests\Feature\Booking;

use App\Models\Employee;
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

    public function test_resolve_creates_a_new_pending_entry_attributed_to_the_submitter(): void
    {
        $employee = Employee::factory()->create();

        $resolved = $this->resolver()->resolve('Fachverfahren IBX', $employee->id);

        $this->assertSame('Fachverfahren IBX', $resolved->name);
        $this->assertFalse($resolved->is_standard);
        $this->assertTrue($resolved->isPending());
        $this->assertDatabaseHas('software_catalog', [
            'name' => 'Fachverfahren IBX',
            'is_standard' => false,
            'status' => SoftwareCatalog::STATUS_PENDING,
            'submitted_by' => $employee->id,
        ]);
    }

    public function test_resolve_does_not_reuse_another_users_pending_entry(): void
    {
        $userA = Employee::factory()->create();
        $userB = Employee::factory()->create();

        // „Excel" wurde von A eingereicht, ist aber noch nicht freigegeben.
        SoftwareCatalog::factory()->pending()->create(['name' => 'Excel', 'submitted_by' => $userA->id]);

        // B tippt denselben Namen → eigener pending-Eintrag, kein Teilen.
        $resolved = $this->resolver()->resolve('excel', $userB->id);

        $this->assertTrue($resolved->isPending());
        $this->assertSame($userB->id, $resolved->submitted_by);
        $this->assertSame(2, SoftwareCatalog::count());
    }

    public function test_resolve_reuses_the_submitters_own_pending_entry(): void
    {
        $employee = Employee::factory()->create();
        $own = SoftwareCatalog::factory()->pending()->create(['name' => 'Excel', 'submitted_by' => $employee->id]);

        $resolved = $this->resolver()->resolve('EXCEL', $employee->id);

        $this->assertTrue($resolved->is($own));
        $this->assertSame(1, SoftwareCatalog::count());
    }

    public function test_resolve_prefers_an_approved_entry_over_creating_pending(): void
    {
        $employee = Employee::factory()->create();
        $approved = SoftwareCatalog::factory()->create(['name' => 'Excel']); // approved per default

        $resolved = $this->resolver()->resolve('excel', $employee->id);

        $this->assertTrue($resolved->is($approved));
        $this->assertSame(1, SoftwareCatalog::count());
    }

    public function test_normalize_trims_filters_and_dedupes_case_insensitively(): void
    {
        $names = $this->resolver()->normalizeNames(['  Office ', 'office', '', '   ', 'GIMP']);

        // Erste Schreibweise gewinnt, Dubletten/Leereinträge entfallen.
        $this->assertSame(['Office', 'GIMP'], $names);
    }
}
