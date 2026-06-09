<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\Employee;
use App\Models\LaptopConfig;
use App\Models\SoftwareCatalog;
use App\Models\TimeSlot;
use App\Services\ImagingSheetExporter;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ImagingSheetTest extends TestCase
{
    use RefreshDatabase;

    private function exporter(): ImagingSheetExporter
    {
        return app(ImagingSheetExporter::class);
    }

    private function bookingWithSoftware(): Booking
    {
        $employee = Employee::factory()->create([
            'vorname' => 'Andrea',
            'nachname' => 'Beispiel',
            'abteilung' => 'IT',
            'pc_nummer' => 'PC-FALLBACK',
        ]);
        $slot = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        LaptopConfig::create(['booking_id' => $booking->id, 'old_pc_nummer' => 'PC-OLD-42']);

        $standard = SoftwareCatalog::factory()->create(['name' => 'MS Office', 'is_standard' => true]);
        $extra = SoftwareCatalog::factory()->create(['name' => 'Adobe Acrobat', 'is_standard' => false]);

        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $standard->id, 'is_custom' => false]);
        BookingSoftware::create(['booking_id' => $booking->id, 'software_catalog_id' => $extra->id, 'is_custom' => false]);
        BookingSoftware::create(['booking_id' => $booking->id, 'custom_software_name' => 'Spezialtool XY', 'is_custom' => true]);

        return $booking;
    }

    public function test_sheet_data_splits_standard_and_requested_software(): void
    {
        $booking = $this->bookingWithSoftware();

        $data = $this->exporter()->sheetData($booking);

        $this->assertSame('Andrea Beispiel', $data['name']);
        $this->assertSame('PC-OLD-42', $data['old_pc']); // aus laptopConfig, nicht Fallback
        $this->assertContains('MS Office', $data['standard']);

        // Standardsoftware taucht NICHT zusätzlich in „angefordert“ auf.
        $this->assertNotContains('MS Office', $data['requested']);
        $this->assertContains('Adobe Acrobat', $data['requested']);
        $this->assertContains('Spezialtool XY (Spezial)', $data['requested']);
    }

    public function test_old_pc_falls_back_to_employee_number_without_config(): void
    {
        $employee = Employee::factory()->create(['pc_nummer' => 'PC-FALLBACK']);
        $booking = Booking::factory()->create(['employee_id' => $employee->id]);

        $this->assertSame('PC-FALLBACK', $this->exporter()->sheetData($booking)['old_pc']);
    }

    public function test_pdf_for_booking_returns_a_pdf_document(): void
    {
        $booking = $this->bookingWithSoftware();

        $pdf = $this->exporter()->pdfForBooking($booking);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_bookings_for_date_excludes_cancellations(): void
    {
        $slot1 = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '09:00:00']);
        $slot2 = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '10:00:00']);
        $other = TimeSlot::factory()->create(['slot_date' => '2026-08-11', 'start_time' => '09:00:00']);

        Booking::factory()->create(['time_slot_id' => $slot1->id, 'status' => 'confirmed']);
        Booking::factory()->cancelled()->create(['time_slot_id' => $slot2->id]);
        Booking::factory()->create(['time_slot_id' => $other->id, 'status' => 'confirmed']);

        $bookings = $this->exporter()->bookingsForDate('2026-08-10');

        $this->assertCount(1, $bookings);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $booking = Booking::factory()->create();

        $this->get(route('admin.exports.imaging.single', $booking))
            ->assertRedirect('/admin/login');
    }

    public function test_admin_and_viewer_can_download_a_single_sheet(): void
    {
        $booking = $this->bookingWithSoftware();

        foreach (['admin', 'viewer'] as $role) {
            $user = AdminUser::factory()->create(['role' => $role]);

            $response = $this->actingAs($user, 'admin')
                ->get(route('admin.exports.imaging.single', $booking));

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function test_day_export_returns_pdf_and_404_when_empty(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $slot = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '09:00:00']);
        Booking::factory()->create(['time_slot_id' => $slot->id, 'status' => 'confirmed']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.exports.imaging.day', ['date' => '2026-08-10']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.exports.imaging.day', ['date' => '2026-09-30']))
            ->assertNotFound();
    }

    public function test_day_action_redirects_to_the_download_route(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        $slot = TimeSlot::factory()->create(['slot_date' => '2026-08-10', 'start_time' => '09:00:00']);
        Booking::factory()->create(['time_slot_id' => $slot->id, 'status' => 'confirmed']);

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->callAction('imagingDayPdf', data: ['date' => '2026-08-10'])
            ->assertRedirect(route('admin.exports.imaging.day', ['date' => '2026-08-10']));
    }

    public function test_day_action_warns_when_no_bookings(): void
    {
        $admin = AdminUser::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->callAction('imagingDayPdf', data: ['date' => '2026-08-10'])
            ->assertNoRedirect();
    }
}
