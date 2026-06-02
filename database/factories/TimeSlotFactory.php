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
        // Eindeutige Kombination aus Werktag + Stunde aus dem 200er-Raster
        // (25 Werktage x 8 Stunden-Slots) – passend zur UNIQUE-Regel
        // (slot_date, start_time), damit mehrere Fenster nicht kollidieren.
        $index = $this->faker->unique()->numberBetween(0, 199);
        $date = Carbon::parse('2026-08-10')->addWeekdays(intdiv($index, 8));
        $hour = 8 + ($index % 8);

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
