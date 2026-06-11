<?php

namespace Tests\Feature\Admin;

use App\Models\SoftwareCatalog;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Wiederherstellung verändert den echten Datenbestand (DROP/CREATE/INSERT), darf
 * also NICHT in einer Transaktion laufen – daher DatabaseMigrations statt
 * RefreshDatabase.
 */
class DatabaseRestoreTest extends TestCase
{
    use DatabaseMigrations;

    /** @var list<string> */
    private array $existing = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->existing = array_map('basename', glob(app(DatabaseBackupService::class)->directory().'/*.sql') ?: []);
    }

    protected function tearDown(): void
    {
        $service = app(DatabaseBackupService::class);
        foreach (glob($service->directory().'/*.sql') ?: [] as $path) {
            if (! in_array(basename($path), $this->existing, true)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_restore_replaces_the_current_data_with_the_backup(): void
    {
        $service = app(DatabaseBackupService::class);

        // Sicherung des leeren Standes erstellen.
        $filename = $service->create('2026-06-11_10-00-00');
        $this->assertSame(0, SoftwareCatalog::count());

        // Danach Daten anlegen (committet, da keine Transaktion).
        SoftwareCatalog::factory()->create(['name' => 'Nur vor dem Restore da']);
        $this->assertSame(1, SoftwareCatalog::count());

        // Sicherung einspielen → der nachträglich angelegte Eintrag verschwindet wieder.
        $service->restore($service->path($filename));

        $this->assertSame(0, SoftwareCatalog::count());
    }
}
