<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use App\Services\BookingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BookingManagerTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): BookingManager
    {
        return app(BookingManager::class);
    }

    public function test_create_books_the_slot_and_marks_it_full(): void
    {
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);

        $booking = $this->manager()->create($employee->id, $slot->id);

        $this->assertSame('confirmed', $booking->status);
        $slot->refresh();
        $this->assertSame(1, $slot->booked_count);
        $this->assertSame('booked', $slot->status);
    }

    public function test_create_rejects_an_already_booked_slot(): void
    {
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1, 'capacity' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nicht mehr verfügbar');

        $this->manager()->create($employee->id, $slot->id);
    }

    public function test_create_rejects_a_second_active_booking_for_the_same_employee(): void
    {
        $employee = Employee::factory()->create();
        $firstSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);
        $secondSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);

        $this->manager()->create($employee->id, $firstSlot->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bereits einen aktiven Termin');

        $this->manager()->create($employee->id, $secondSlot->id);
    }

    public function test_cancel_frees_the_slot_again(): void
    {
        $employee = Employee::factory()->create();
        $slot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $booking = $this->manager()->create($employee->id, $slot->id);

        $this->manager()->cancel($booking, 'Mitarbeiter:in hat abgesagt');

        $booking->refresh();
        $slot->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('Mitarbeiter:in hat abgesagt', $booking->cancellation_reason);
        $this->assertSame(0, $slot->booked_count);
        $this->assertSame('available', $slot->status);
    }

    public function test_move_frees_the_old_slot_and_occupies_the_new_one(): void
    {
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $newSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0, 'capacity' => 1]);
        $booking = $this->manager()->create($employee->id, $oldSlot->id);

        $this->manager()->move($booking, $newSlot->id);

        $booking->refresh();
        $oldSlot->refresh();
        $newSlot->refresh();

        $this->assertSame($newSlot->id, $booking->time_slot_id);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('available', $oldSlot->status);
        $this->assertSame(0, $oldSlot->booked_count);
        $this->assertSame('booked', $newSlot->status);
        $this->assertSame(1, $newSlot->booked_count);
    }

    public function test_move_rejects_an_unavailable_target_slot(): void
    {
        $employee = Employee::factory()->create();
        $oldSlot = TimeSlot::factory()->create(['status' => 'available', 'booked_count' => 0]);
        $fullSlot = TimeSlot::factory()->create(['status' => 'booked', 'booked_count' => 1, 'capacity' => 1]);
        $booking = $this->manager()->create($employee->id, $oldSlot->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nicht mehr verfügbar');

        $this->manager()->move($booking, $fullSlot->id);
    }
}
