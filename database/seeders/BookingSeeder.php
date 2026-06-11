<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\Employee;
use App\Models\LaptopConfig;
use App\Models\SoftwareCatalog;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    /** Anzahl der Testbuchungen. */
    public const COUNT = 50;

    public function run(): void
    {
        $slots = TimeSlot::where('status', 'available')
            ->inRandomOrder()
            ->limit(self::COUNT)
            ->get();

        $employees = Employee::inRandomOrder()
            ->limit($slots->count())
            ->get();

        $standardSoftware = SoftwareCatalog::where('is_standard', true)->get();

        foreach ($slots as $index => $slot) {
            $employee = $employees[$index];

            $booking = Booking::create([
                'employee_id' => $employee->id,
                'time_slot_id' => $slot->id,
                'status' => 'confirmed',
                'booked_at' => Carbon::now()->subDays(random_int(0, 14)),
            ]);

            // Zeitfenster als belegt markieren (Kapazität 1).
            $slot->update([
                'status' => 'booked',
                'booked_count' => 1,
            ]);

            // Altgerät-Konfiguration (PC-Nummer aus dem Mitarbeiterdatensatz).
            LaptopConfig::factory()->create([
                'booking_id' => $booking->id,
                'old_pc_nummer' => $employee->pc_nummer,
            ]);

            // Eine zufällige Auswahl an Standardsoftware ...
            foreach ($standardSoftware->random(min(4, $standardSoftware->count())) as $software) {
                BookingSoftware::create([
                    'booking_id' => $booking->id,
                    'software_catalog_id' => $software->id,
                    'is_custom' => false,
                ]);
            }

            // ... und bei jeder dritten Buchung eine Spezialsoftware als Freitext.
            if ($index % 3 === 0) {
                BookingSoftware::create([
                    'booking_id' => $booking->id,
                    'custom_software_name' => 'Fachverfahren '.mb_strtoupper(fake()->lexify('???')),
                    'is_custom' => true,
                ]);
            }
        }

        $this->seedPendingSoftware($slots);
    }

    /**
     * Ein paar von Mitarbeitenden eingereichte, noch nicht freigegebene Einträge –
     * damit der Freigabe-Workflow im Admin-Panel etwas zum Prüfen hat.
     *
     * @param  \Illuminate\Support\Collection<int, TimeSlot>  $slots
     */
    private function seedPendingSoftware($slots): void
    {
        $names = ['Greenshot', 'IrfanView', 'KeePass', 'FileZilla'];

        $bookings = Booking::with('employee')->inRandomOrder()->limit(count($names))->get();

        foreach ($bookings as $i => $booking) {
            $software = SoftwareCatalog::create([
                'name' => $names[$i],
                'is_standard' => false,
                'status' => SoftwareCatalog::STATUS_PENDING,
                'submitted_by' => $booking->employee_id,
            ]);

            BookingSoftware::create([
                'booking_id' => $booking->id,
                'software_catalog_id' => $software->id,
                'is_custom' => false,
            ]);
        }
    }
}
