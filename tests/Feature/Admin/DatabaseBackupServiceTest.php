<?php

namespace Tests\Feature\Admin;

use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> Vor dem Test vorhandene Sicherungen – werden nicht angetastet. */
    private array $existing = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->existing = array_map('basename', glob(app(DatabaseBackupService::class)->directory().'/*.sql') ?: []);
    }

    protected function tearDown(): void
    {
        // Nur die im Test erzeugten Dateien wieder entfernen.
        $service = app(DatabaseBackupService::class);
        foreach (glob($service->directory().'/*.sql') ?: [] as $path) {
            if (! in_array(basename($path), $this->existing, true)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function service(): DatabaseBackupService
    {
        return app(DatabaseBackupService::class);
    }

    public function test_create_writes_a_dump_file_with_a_timestamped_name(): void
    {
        $filename = $this->service()->create();

        $this->assertMatchesRegularExpression(DatabaseBackupService::FILENAME_PATTERN, $filename);

        $contents = file_get_contents($this->service()->path($filename));
        $this->assertStringContainsString('CREATE TABLE', $contents);
        $this->assertStringContainsString('software_catalog', $contents);
    }

    public function test_all_lists_backups_with_metadata(): void
    {
        $filename = $this->service()->create('2026-06-11_08-00-00');

        $backup = $this->service()->all()->firstWhere('filename', $filename);

        $this->assertNotNull($backup);
        $this->assertGreaterThan(0, $backup['size']);
        $this->assertInstanceOf(Carbon::class, $backup['date']);
    }

    public function test_delete_removes_a_backup(): void
    {
        $filename = $this->service()->create('2026-06-11_09-00-00');
        $this->assertTrue($this->service()->exists($filename));

        $this->service()->delete($filename);

        $this->assertFalse($this->service()->exists($filename));
    }

    public function test_path_rejects_manipulated_filenames(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service()->path('../../../etc/passwd');
    }

    public function test_exists_is_false_for_names_outside_the_scheme(): void
    {
        $this->assertFalse($this->service()->exists('beliebig.sql'));
        $this->assertFalse($this->service()->exists('backup_2026-06-11.sql'));
    }

    public function test_restore_throws_when_the_file_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service()->restore($this->service()->directory().'/gibt-es-nicht.sql');
    }
}
