<?php

namespace Tests\Feature\Booking;

use App\Models\DownloadFile;
use App\Models\Employee;
use App\Services\DownloadFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DashboardDownloadsTest extends TestCase
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

    public function test_dashboard_hides_the_download_section_when_no_files_exist(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Dokumente &amp; Anleitungen', false);
    }

    public function test_dashboard_shows_files_for_download_when_present(): void
    {
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );
        $employee = Employee::factory()->create();

        $this->actingAs($employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dokumente &amp; Anleitungen', false)
            ->assertSee('Anleitung.pdf')
            ->assertSee(route('downloads.show', $file));
    }

    public function test_employee_can_download_a_file(): void
    {
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );
        $employee = Employee::factory()->create();

        $this->actingAs($employee, 'employee')
            ->get(route('downloads.show', $file))
            ->assertOk()
            ->assertDownload('Anleitung.pdf');
    }

    public function test_guest_cannot_download_a_file(): void
    {
        $file = app(DownloadFileService::class)->store(
            UploadedFile::fake()->createWithContent('Anleitung.pdf', '%PDF-1.4 Testinhalt'),
        );

        $this->get(route('downloads.show', $file))->assertRedirect(route('login'));
    }
}
