<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reihenfolge ist wichtig (Fremdschlüssel-Abhängigkeiten):
     * Admins & Software & Mitarbeitende & Zeitfenster zuerst, dann Buchungen.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SoftwareCatalogSeeder::class,
            EmployeeSeeder::class,
            TimeSlotSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
