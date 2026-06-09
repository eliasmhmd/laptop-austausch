<?php

namespace Database\Seeders;

use App\Services\SlotGenerator;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /** Buchungszeitraum (inklusive). */
    public const START_DATE = '2026-08-10';

    public const END_DATE = '2026-09-11';

    public function run(): void
    {
        // Gleiche Logik wie der „Slots generieren"-Button im Admin-Panel.
        app(SlotGenerator::class)->generate(self::START_DATE, self::END_DATE);
    }
}
