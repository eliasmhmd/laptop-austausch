<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomSettingTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFor(Employee $employee): Booking
    {
        $slot = TimeSlot::factory()->create();

        return Booking::create([
            'employee_id' => $employee->id,
            'time_slot_id' => $slot->id,
            'status' => 'confirmed',
            'booked_at' => now(),
        ]);
    }

    public function test_setting_get_returns_default_when_unset(): void
    {
        $this->assertSame('Standard', Setting::get('nicht_da', 'Standard'));
    }

    public function test_setting_set_then_get_roundtrips(): void
    {
        Setting::set(Setting::ROOM_KEY, 'Raum 345');

        $this->assertSame('Raum 345', Setting::get(Setting::ROOM_KEY));
        $this->assertSame('Raum 345', Setting::room());
    }

    public function test_room_falls_back_when_unset(): void
    {
        $this->assertSame(Setting::ROOM_FALLBACK, Setting::room());
    }

    public function test_confirmation_page_shows_the_room(): void
    {
        Setting::set(Setting::ROOM_KEY, 'Raum 345');
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('booking.show', $booking))
            ->assertOk()
            ->assertSee('Ort')
            ->assertSee('Raum 345');
    }

    public function test_dashboard_greets_with_full_name_and_shows_the_room(): void
    {
        Setting::set(Setting::ROOM_KEY, 'Raum 345');
        $employee = Employee::factory()->create(['vorname' => 'Max', 'nachname' => 'Mustermann']);
        $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Willkommen, Max Mustermann!', escape: false)
            ->assertSee('Raum 345');
    }

    public function test_ical_contains_the_room_as_location(): void
    {
        Setting::set(Setting::ROOM_KEY, 'Raum 345');
        $employee = Employee::factory()->create();
        $booking = $this->bookingFor($employee);

        $this->actingAs($employee, 'employee')
            ->get(route('booking.ical', $booking))
            ->assertOk()
            ->assertSee('LOCATION:Raum 345', false);
    }
}
