<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_the_ical_file(): void
    {
        $employee = Employee::factory()->create();
        $booking = Booking::factory()->create(['employee_id' => $employee->id, 'status' => 'confirmed']);

        $response = $this->actingAs($employee, 'employee')->get(route('booking.ical', $booking));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertDownload('laptop-austausch-termin.ics');

        $body = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('BEGIN:VEVENT', $body);
        $this->assertStringContainsString('UID:booking-'.$booking->id.'@', $body);
        $this->assertStringContainsString('SUMMARY:Laptop-Austausch', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
    }

    public function test_times_are_converted_to_utc(): void
    {
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->booked()->create([
            'slot_date' => '2026-08-10',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);
        $booking = Booking::factory()->create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
        ]);

        $body = $this->actingAs($employee, 'employee')->get(route('booking.ical', $booking))->getContent();

        // 08:00 Europe/Berlin im August (CEST, UTC+2) -> 06:00Z.
        $this->assertStringContainsString('DTSTART:20260810T060000Z', $body);
        $this->assertStringContainsString('DTEND:20260810T070000Z', $body);
    }

    public function test_non_owner_cannot_download_the_ical_file(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $this->actingAs(Employee::factory()->create(), 'employee')
            ->get(route('booking.ical', $booking))
            ->assertForbidden();
    }
}
