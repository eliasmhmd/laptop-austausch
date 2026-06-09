<?php

namespace Tests\Feature\Admin;

use App\Exports\BookingsExport;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class BookingsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_has_german_headings(): void
    {
        $export = new BookingsExport(Booking::query());

        $this->assertSame(
            ['KVGG-Nr.', 'Name', 'Abteilung', 'E-Mail', 'Datum', 'Uhrzeit', 'KW', 'Status', 'Gebucht am'],
            $export->headings(),
        );
    }

    public function test_export_maps_a_booking_row(): void
    {
        $employee = Employee::factory()->create([
            'kvgg_nummer' => 'KVGG-1',
            'vorname' => 'Max',
            'nachname' => 'Mustermann',
            'abteilung' => 'IT',
            'email' => 'max@kreisgg.de',
        ]);
        $slot = TimeSlot::factory()->create([
            'slot_date' => '2026-08-10',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'calendar_week' => 33,
        ]);
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'no_show',
        ]);

        $row = (new BookingsExport(Booking::query()))->map($booking->fresh(['employee', 'timeSlot']));

        $this->assertSame('KVGG-1', $row[0]);
        $this->assertSame('Max Mustermann', $row[1]);
        $this->assertSame('IT', $row[2]);
        $this->assertSame('max@kreisgg.de', $row[3]);
        $this->assertSame('10.08.2026', $row[4]);
        $this->assertSame('09:00–10:00', $row[5]);
        $this->assertSame('KW 33', $row[6]);
        $this->assertSame('nicht erschienen', $row[7]); // deutscher Status-Label
    }

    public function test_admin_can_download_the_excel_export(): void
    {
        Excel::fake();
        $admin = AdminUser::factory()->create(['role' => 'admin']);
        Booking::factory()->count(3)->create();

        Livewire::actingAs($admin, 'admin')
            ->test(ListBookings::class)
            ->callAction('exportExcel');

        Excel::assertDownloaded('buchungen-'.now()->format('Y-m-d').'.xlsx');
    }
}
