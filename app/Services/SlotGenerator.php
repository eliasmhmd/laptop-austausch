<?php

namespace App\Services;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Erzeugt Zeitfenster (Slots) für einen Datumsbereich: Werktags, 8 Slots/Tag,
 * Wochenenden und Feiertage werden ausgelassen. Wird sowohl vom Seeder als auch
 * vom Admin-Panel („Slots generieren") verwendet, damit die Logik identisch ist.
 */
class SlotGenerator
{
    /** 8 Slots pro Werktag, jeweils eine Stunde. */
    public const START_HOURS = [8, 9, 10, 11, 12, 13, 14, 15];

    /**
     * Feiertage in Hessen, die ausgelassen werden (Y-m-d).
     *
     * @var list<string>
     */
    public const HOLIDAYS = [];

    /**
     * Erzeugt alle Slots im Bereich (inklusive beider Enden). Bereits vorhandene
     * Slots (gleiches Datum + Startzeit) werden nicht doppelt angelegt.
     *
     * @return array{created: int, existing: int, days: int}
     */
    public function generate(Carbon|string $start, Carbon|string $end, ?int $createdBy = null): array
    {
        $period = CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end));

        $created = 0;
        $existing = 0;
        $days = 0;

        foreach ($period as $day) {
            /** @var Carbon $day */
            if ($day->isWeekend() || in_array($day->format('Y-m-d'), self::HOLIDAYS, true)) {
                continue;
            }

            $days++;

            foreach (self::START_HOURS as $hour) {
                $slot = TimeSlot::firstOrCreate(
                    [
                        'slot_date' => $day->format('Y-m-d'),
                        'start_time' => sprintf('%02d:00:00', $hour),
                    ],
                    [
                        'end_time' => sprintf('%02d:00:00', $hour + 1),
                        'calendar_week' => (int) $day->isoWeek(),
                        'status' => 'available',
                        'capacity' => 1,
                        'booked_count' => 0,
                        'created_by' => $createdBy,
                    ],
                );

                $slot->wasRecentlyCreated ? $created++ : $existing++;
            }
        }

        return ['created' => $created, 'existing' => $existing, 'days' => $days];
    }
}
