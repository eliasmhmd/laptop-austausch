<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        return Employee::factory()->create();
    }

    public function test_calendar_is_shown_to_employee_without_booking(): void
    {
        TimeSlot::factory()->count(3)->create();

        $this->actingAs($this->employee(), 'employee')
            ->get(route('booking.calendar'))
            ->assertOk()
            ->assertSee('Kalenderwoche');
    }

    public function test_employee_can_book_an_available_slot(): void
    {
        $employee = $this->employee();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);

        $response = $this->actingAs($employee, 'employee')
            ->post(route('booking.store', $slot));

        $booking = Booking::firstWhere('employee_id', $employee->id);

        $this->assertNotNull($booking);
        // Nach der Buchung direkt zur Software-Erfassung.
        $response->assertRedirect(route('config.edit', $booking));

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('booked', $slot->refresh()->status);
        $this->assertSame(1, $slot->booked_count);
    }

    public function test_cannot_book_an_already_booked_slot(): void
    {
        $slot = TimeSlot::factory()->booked()->create(['capacity' => 1]);

        $this->actingAs($this->employee(), 'employee')
            ->post(route('booking.store', $slot))
            ->assertRedirect(route('booking.calendar'))
            ->assertSessionHasErrors('slot');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_employee_with_active_booking_is_redirected_from_calendar(): void
    {
        $employee = $this->employee();
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($employee, 'employee')
            ->get(route('booking.calendar'))
            ->assertRedirect(route('booking.show', $booking));
    }

    public function test_employee_cannot_hold_two_active_bookings(): void
    {
        $employee = $this->employee();
        Booking::factory()->create(['employee_id' => $employee->id, 'status' => 'confirmed']);
        $freeSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);

        $this->actingAs($employee, 'employee')
            ->post(route('booking.store', $freeSlot))
            ->assertRedirect(route('booking.calendar'));

        $this->assertSame('available', $freeSlot->refresh()->status);
        $this->assertSame(1, Booking::where('employee_id', $employee->id)->count());
    }

    public function test_availability_endpoint_lists_only_free_slots(): void
    {
        $free = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $taken = TimeSlot::factory()->booked()->create(['capacity' => 1]);

        $response = $this->actingAs($this->employee(), 'employee')
            ->getJson(route('booking.availability'));

        $response->assertOk();
        $this->assertContains($free->id, $response->json('available'));
        $this->assertNotContains($taken->id, $response->json('available'));
    }

    public function test_slot_check_endpoint_reports_availability(): void
    {
        $employee = $this->employee();
        $free = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $taken = TimeSlot::factory()->booked()->create(['capacity' => 1]);

        $this->actingAs($employee, 'employee')
            ->getJson(route('booking.slot-check', $free))
            ->assertOk()
            ->assertJson(['available' => true]);

        $this->actingAs($employee, 'employee')
            ->getJson(route('booking.slot-check', $taken))
            ->assertJson(['available' => false]);
    }

    public function test_employee_cannot_view_another_employees_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $this->actingAs($this->employee(), 'employee')
            ->get(route('booking.show', $booking))
            ->assertForbidden();
    }

    public function test_guests_cannot_access_the_calendar(): void
    {
        $this->get(route('booking.calendar'))->assertRedirect('/login');
    }
}
