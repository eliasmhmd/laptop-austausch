<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Downloads;
use App\Models\AdminUser;
use App\Models\DownloadFile;
use App\Services\DownloadFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadsPageTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $existing = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->existing = array_map('basename', glob(DownloadFile::directory().'/*') ?: []);
    }

    protected function tearDown(): void
    {
        foreach (glob(DownloadFile::directory().'/*') ?: [] as $path) {
            if (! in_array(basename($path), $this->existing, true)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_the_page(): void
    {
        $this->get('/admin/downloads')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_page(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get('/admin/downloads')->assertOk();
    }

    public function test_viewer_cannot_open_the_page(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get('/admin/downloads')->assertForbidden();
    }

    public function test_service_stores_an_uploaded_file_with_metadata(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
            $admin->id,
        );

        $this->assertSame('Anleitung.pdf', $file->original_name);
        $this->assertSame($admin->id, $file->uploaded_by);
        $this->assertGreaterThan(0, $file->size);
        $this->assertFileExists($file->path());
        $this->assertNotSame('Anleitung.pdf', $file->stored_name); // zufälliger Speichername
    }

    public function test_admin_can_delete_a_file_from_the_page(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );
        $path = $file->path();

        Livewire::actingAs($admin, 'admin')
            ->test(Downloads::class)
            ->call('deleteDownload', $file->id);

        $this->assertDatabaseMissing('download_files', ['id' => $file->id]);
        $this->assertFileDoesNotExist($path);
    }

    public function test_guest_cannot_download_via_admin_route(): void
    {
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );

        $this->get(route('admin.downloads.download', $file))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_viewer_cannot_download_via_admin_route(): void
    {
        $viewer = AdminUser::factory()->create(['role' => 'viewer']);
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.downloads.download', $file))
            ->assertForbidden();
    }

    public function test_admin_downloads_an_existing_file(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.downloads.download', $file))
            ->assertOk()
            ->assertDownload('Anleitung.pdf');
    }
}
