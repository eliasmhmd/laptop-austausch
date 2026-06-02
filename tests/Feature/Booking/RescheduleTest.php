<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RescheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Erzeugt eine:n Mitarbeiter:in mit einem aktiven Termin auf einem belegten Slot.
     *
     * @return array{0: Employee, 1: Booking, 2: TimeSlot}
     */
    private function employeeWithBooking(): array
    {
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->booked()->create(['capacity' => 1]);
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $oldSlot->id,
            'status' => 'confirmed',
        ]);

        return [$employee, $booking, $oldSlot];
    }

    public function test_reschedule_page_requires_an_existing_booking(): void
    {
        $this->actingAs(Employee::factory()->create(), 'employee')
            ->get(route('reschedule.edit'))
            ->assertRedirect(route('booking.calendar'));
    }

    public function test_reschedule_page_shows_current_booking(): void
    {
        [$employee] = $this->employeeWithBooking();

        $this->actingAs($employee, 'employee')
            ->get(route('reschedule.edit'))
            ->assertOk()
            ->assertSee('Aktueller Termin');
    }

    public function test_employee_can_move_booking_to_a_new_slot(): void
    {
        [$employee, $oldBooking, $oldSlot] = $this->employeeWithBooking();
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);

        $this->actingAs($employee, 'employee')
            ->post(route('reschedule.update', $newSlot))
            ->assertRedirect();

        // Alter Termin storniert, altes Fenster wieder frei (grün).
        $this->assertSame('cancelled', $oldBooking->refresh()->status);
        $this->assertSame('available', $oldSlot->refresh()->status);
        $this->assertSame(0, $oldSlot->booked_count);

        // Neues Fenster belegt (rot), genau ein aktiver Termin.
        $this->assertSame('booked', $newSlot->refresh()->status);
        $this->assertSame(1, $newSlot->booked_count);
        $this->assertSame(1, Booking::where('employee_id', $employee->id)->where('status', 'confirmed')->count());
    }

    public function test_cannot_reschedule_onto_a_booked_slot(): void
    {
        [$employee, $oldBooking] = $this->employeeWithBooking();
        $takenSlot = TimeSlot::factory()->booked()->create(['capacity' => 1]);

        $this->actingAs($employee, 'employee')
            ->post(route('reschedule.update', $takenSlot))
            ->assertRedirect(route('reschedule.edit'))
            ->assertSessionHasErrors('slot');

        $this->assertSame('confirmed', $oldBooking->refresh()->status);
    }

    public function test_cannot_reschedule_onto_the_same_slot(): void
    {
        [$employee, , $oldSlot] = $this->employeeWithBooking();

        $this->actingAs($employee, 'employee')
            ->get(route('reschedule.confirm', $oldSlot))
            ->assertRedirect(route('reschedule.edit'))
            ->assertSessionHasErrors('slot');
    }

    public function test_confirm_page_shows_old_and_new_slot(): void
    {
        [$employee] = $this->employeeWithBooking();
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);

        $this->actingAs($employee, 'employee')
            ->get(route('reschedule.confirm', $newSlot))
            ->assertOk()
            ->assertSee('Bisher')
            ->assertSee('Neu');
    }
}
