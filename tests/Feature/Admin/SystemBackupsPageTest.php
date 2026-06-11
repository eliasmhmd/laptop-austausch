<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\SystemBackups;
use App\Models\AdminUser;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemBackupsPageTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_guest_is_redirected_from_the_page(): void
    {
        $this->get('/admin/system-backups')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_page(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin/system-backups')->assertOk();
    }

    public function test_viewer_cannot_open_the_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/system-backups')->assertForbidden();
    }

    public function test_admin_can_create_and_delete_a_backup_from_the_page(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $service = app(DatabaseBackupService::class);

        Livewire::actingAs($admin, 'admin')
            ->test(SystemBackups::class)
            ->callAction('createBackup');

        $backup = $service->all()->first();
        $this->assertNotNull($backup);

        Livewire::actingAs($admin, 'admin')
            ->test(SystemBackups::class)
            ->call('deleteBackup', $backup['filename']);

        $this->assertFalse($service->exists($backup['filename']));
    }

    public function test_guest_cannot_download_a_backup(): void
    {
        $this->get(route('admin.backups.download', 'backup_2026-06-11_10-00-00.sql'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_viewer_cannot_download_a_backup(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.backups.download', 'backup_2026-06-11_10-00-00.sql'))
            ->assertForbidden();
    }

    public function test_admin_downloads_an_existing_backup(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $filename = app(DatabaseBackupService::class)->create('2026-06-11_11-00-00');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.backups.download', $filename))
            ->assertOk()
            ->assertDownload($filename);
    }

    public function test_admin_download_of_a_missing_backup_returns_404(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.backups.download', 'backup_1999-01-01_00-00-00.sql'))
            ->assertNotFound();
    }
}
