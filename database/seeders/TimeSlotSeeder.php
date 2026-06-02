<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /** Buchungszeitraum (inklusive). */
    public const START_DATE = '2026-08-10';

    public const END_DATE = '2026-09-11';

    /** 8 Slots pro Werktag, jeweils eine Stunde. */
    public const START_HOURS = [8, 9, 10, 11, 12, 13, 14, 15];

    /**
     * Feiertage in Hessen, die im Zeitraum ausgelassen werden (Y-m-d).
     * Aug–Sep 2026 enthält keine hessischen Feiertage; Liste dient als Hook.
     *
     * @var list<string>
     */
    public const HOLIDAYS = [];

    public function run(): void
    {
        $period = CarbonPeriod::create(self::START_DATE, self::END_DATE);

        foreach ($period as $day) {
            /** @var Carbon $day */
            if ($day->isWeekend() || in_array($day->format('Y-m-d'), self::HOLIDAYS, true)) {
                continue;
            }

            foreach (self::START_HOURS as $hour) {
                TimeSlot::updateOrCreate(
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
                    ],
                );
            }
        }
    }
}
