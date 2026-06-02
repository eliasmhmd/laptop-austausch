<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'time_slot_id' => TimeSlot::factory(),
            'status' => 'confirmed',
            'cancellation_reason' => null,
            'unplanned_note' => null,
            'booked_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }
}
