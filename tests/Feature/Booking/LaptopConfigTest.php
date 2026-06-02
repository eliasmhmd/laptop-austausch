<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\Employee;
use App\Models\LaptopConfig;
use App\Models\SoftwareCatalog;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaptopConfigTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFor(Employee $employee): Booking
    {
        return Booking::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_owner_can_open_the_config_form(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('config.edit', $booking))
            ->assertOk()
            ->assertSee('Installierte Software');
    }

    public function test_non_owner_cannot_open_the_config_form(): void
    {
        $booking = $this->bookingFor(Employee::factory()->create());

        $this->actingAs(Employee::factory()->create(), 'employee')
            ->get(route('config.edit', $booking))
            ->assertForbidden();
    }

    public function test_saving_config_stores_manufacturer_software_and_custom(): void
    {
        $employee = Employee::factory()->create(['pc_nummer' => 'PC7438']);
        $booking = $this->bookingFor($employee);
        $office = SoftwareCatalog::factory()->create(['name' => 'Microsoft Office']);
        $teams = SoftwareCatalog::factory()->create(['name' => 'Microsoft Teams']);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), [
                'manufacturer' => 'Lenovo',
                'software' => [$office->id, $teams->id],
                'custom_software' => "Fachverfahren XY\nSpezialtool Z",
            ])
            ->assertRedirect(route('booking.show', $booking));

        $config = LaptopConfig::firstWhere('booking_id', $booking->id);
        $this->assertSame('Lenovo', $config->old_manufacturer);
        // PC-Nummer wird automatisch aus dem Mitarbeiterdatensatz übernommen.
        $this->assertSame('PC7438', $config->old_pc_nummer);

        $this->assertSame(2, BookingSoftware::where('booking_id', $booking->id)->where('is_custom', false)->count());
        $this->assertSame(2, BookingSoftware::where('booking_id', $booking->id)->where('is_custom', true)->count());
        $this->assertDatabaseHas('booking_software', [
            'booking_id' => $booking->id,
            'custom_software_name' => 'Fachverfahren XY',
            'is_custom' => true,
        ]);
    }

    public function test_resaving_replaces_previous_selection_without_duplicates(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);
        $a = SoftwareCatalog::factory()->create();
        $b = SoftwareCatalog::factory()->create();

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software' => [$a->id, $b->id], 'custom_software' => 'Tool A']);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software' => [$a->id], 'custom_software' => '']);

        $this->assertSame(1, BookingSoftware::where('booking_id', $booking->id)->count());
        $this->assertDatabaseHas('booking_software', ['booking_id' => $booking->id, 'software_catalog_id' => $a->id]);
    }

    public function test_rejects_software_id_outside_catalog(): void
    {
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $booking), ['software' => [999999]])
            ->assertSessionHasErrors('software.0');
    }

    public function test_config_and_software_move_to_new_booking_on_reschedule(): void
    {
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->booked()->create(['capacity' => 1]);
        $oldBooking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $oldSlot->id,
            'status' => 'confirmed',
        ]);
        $software = SoftwareCatalog::factory()->create();

        // Software/Hersteller für die alte Buchung erfassen.
        $this->actingAs($employee, 'employee')
            ->post(route('config.update', $oldBooking), [
                'manufacturer' => 'Dell',
                'software' => [$software->id],
                'custom_software' => 'Spezialtool',
            ]);

        // Auf ein neues Fenster verschieben.
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $this->actingAs($employee, 'employee')->post(route('reschedule.update', $newSlot));

        $newBooking = Booking::where('employee_id', $employee->id)->where('status', 'confirmed')->firstOrFail();

        // Angaben hängen jetzt an der neuen Buchung, nicht mehr an der alten.
        $this->assertDatabaseHas('laptop_configs', ['booking_id' => $newBooking->id, 'old_manufacturer' => 'Dell']);
        $this->assertSame(2, BookingSoftware::where('booking_id', $newBooking->id)->count());
        $this->assertSame(0, BookingSoftware::where('booking_id', $oldBooking->id)->count());
    }
}
