<?php

namespace Database\Factories;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 *
 * Hinweis: Im Seeder werden die Zeitfenster deterministisch über den
 * Datumsbereich erzeugt (siehe TimeSlotSeeder). Diese Factory dient vor allem
 * Tests.
 */
class TimeSlotFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::parse($this->faker->dateTimeBetween('2026-08-10', '2026-09-11'));
        $hour = $this->faker->numberBetween(8, 15);

        return [
            'slot_date' => $date->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $hour),
            'end_time' => sprintf('%02d:00:00', $hour + 1),
            'calendar_week' => (int) $date->isoWeek(),
            'status' => 'available',
            'capacity' => 1,
            'booked_count' => 0,
            'created_by' => null,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'booked',
            'booked_count' => 1,
        ]);
    }
}
