<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Buchungsverwaltung für das Admin-Panel: Anlegen, Stornieren und Verschieben
 * von Terminen – jeweils in einer Transaktion mit Zeilensperren (lockForUpdate),
 * damit keine Doppelbuchung entsteht.
 *
 * Hinweis: Die Mitarbeiter-Seite (BookingController/RescheduleController) hat
 * ihre eigene, getestete Logik. Die Slot-Belegung/-Freigabe liegt daher aktuell
 * an zwei Stellen; eine spätere Vereinheitlichung wäre denkbar.
 */
class BookingManager
{
    /**
     * Manuelle Buchung anlegen. Wirft RuntimeException mit deutscher Meldung,
     * wenn das Zeitfenster belegt ist oder die Person bereits einen Termin hat.
     */
    public function create(int $employeeId, int $timeSlotId): Booking
    {
        return DB::transaction(function () use ($employeeId, $timeSlotId): Booking {
            $employee = Employee::whereKey($employeeId)->lockForUpdate()->first();

            if (! $employee) {
                throw new RuntimeException('Mitarbeiter:in nicht gefunden.');
            }

            if ($employee->activeBookings()->lockForUpdate()->exists()) {
                throw new RuntimeException('Diese:r Mitarbeiter:in hat bereits einen aktiven Termin.');
            }

            $slot = TimeSlot::whereKey($timeSlotId)->lockForUpdate()->first();

            if (! $slot || ! $slot->isAvailable()) {
                throw new RuntimeException('Dieses Zeitfenster ist nicht mehr verfügbar.');
            }

            $booking = Booking::create([
                'employee_id' => $employee->id,
                'time_slot_id' => $slot->id,
                'status' => 'confirmed',
                'booked_at' => now(),
            ]);

            $this->occupy($slot);

            return $booking;
        });
    }

    /**
     * Buchung stornieren und das Zeitfenster wieder freigeben.
     */
    public function cancel(Booking $booking, ?string $reason = null): void
    {
        DB::transaction(function () use ($booking, $reason): void {
            $fresh = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status === 'cancelled') {
                throw new RuntimeException('Diese Buchung ist bereits storniert.');
            }

            $slot = TimeSlot::whereKey($fresh->time_slot_id)->lockForUpdate()->first();

            $fresh->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason ?: 'Von der Verwaltung storniert',
            ]);

            if ($slot) {
                $this->free($slot);
            }

            $booking->refresh();
        });
    }

    /**
     * Buchung in ein anderes (freies) Zeitfenster verschieben: altes Fenster
     * freigeben, neues belegen – die Buchung selbst (inkl. Geräte-/Software-
     * angaben) bleibt erhalten.
     */
    public function move(Booking $booking, int $newTimeSlotId): void
    {
        DB::transaction(function () use ($booking, $newTimeSlotId): void {
            $fresh = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status === 'cancelled') {
                throw new RuntimeException('Stornierte Buchungen können nicht verschoben werden.');
            }

            if ($fresh->time_slot_id === $newTimeSlotId) {
                throw new RuntimeException('Das ist bereits das gebuchte Zeitfenster.');
            }

            $newSlot = TimeSlot::whereKey($newTimeSlotId)->lockForUpdate()->first();

            if (! $newSlot || ! $newSlot->isAvailable()) {
                throw new RuntimeException('Dieses Zeitfenster ist nicht mehr verfügbar.');
            }

            $oldSlot = TimeSlot::whereKey($fresh->time_slot_id)->lockForUpdate()->first();

            if ($oldSlot) {
                $this->free($oldSlot);
            }

            $fresh->update(['time_slot_id' => $newSlot->id]);

            $this->occupy($newSlot);

            $booking->refresh();
        });
    }

    /**
     * Gibt alle von diesen Mitarbeitenden belegten Zeitfenster wieder frei.
     * Nötig VOR dem endgültigen Löschen: der Datenbank-Cascade entfernt zwar die
     * Buchungen, setzt aber den Slot-Zähler/-Status nicht zurück – die Fenster
     * blieben sonst fälschlich „belegt“.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Employee>|array<int, int>  $employeeIds
     */
    public function releaseSlotsForEmployees(iterable $employeeIds): void
    {
        $ids = collect($employeeIds)
            ->map(fn ($e) => $e instanceof Employee ? $e->getKey() : $e)
            ->all();

        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            // time_slot_id ist auf bookings eindeutig, daher max. eine Buchung
            // pro Fenster. Stornierte Buchungen haben ihr Fenster bereits frei-
            // gegeben und werden ausgelassen.
            $slotIds = Booking::query()
                ->whereIn('employee_id', $ids)
                ->where('status', '!=', 'cancelled')
                ->pluck('time_slot_id');

            TimeSlot::query()
                ->whereIn('id', $slotIds)
                ->lockForUpdate()
                ->get()
                ->each(fn (TimeSlot $slot) => $this->free($slot));
        });
    }

    /**
     * Gibt die von diesen Buchungen belegten Zeitfenster wieder frei – nötig VOR
     * dem Löschen einzelner Buchungen (der DB-Cascade entfernt die Buchung, setzt
     * aber den Slot-Zähler/-Status nicht zurück). Stornierte Buchungen werden
     * übersprungen, da deren Fenster bereits frei sind.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Booking>|array<int, int>  $bookings
     */
    public function releaseSlotsForBookings(iterable $bookings): void
    {
        $ids = collect($bookings)
            ->map(fn ($b) => $b instanceof Booking ? $b->getKey() : $b)
            ->all();

        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            $slotIds = Booking::query()
                ->whereIn('id', $ids)
                ->where('status', '!=', 'cancelled')
                ->pluck('time_slot_id');

            TimeSlot::query()
                ->whereIn('id', $slotIds)
                ->lockForUpdate()
                ->get()
                ->each(fn (TimeSlot $slot) => $this->free($slot));
        });
    }

    /**
     * Setzt die Daten für einen neuen Austausch-Durchlauf zurück: löscht ALLE
     * Buchungen und ALLE Mitarbeitenden und gibt sämtliche Zeitfenster wieder
     * frei. Der Software-Katalog und die generierten Zeitfenster bleiben erhalten.
     *
     * @return array{bookings: int, employees: int} Anzahl gelöschter Datensätze
     */
    public function purgeAllBookingsAndEmployees(): array
    {
        return DB::transaction(function (): array {
            $counts = [
                'bookings' => Booking::count(),
                'employees' => Employee::count(),
            ];

            // Buchungen zuerst: der DB-Cascade entfernt Software-Verknüpfungen
            // (booking_software) und Geräte-Konfigurationen (laptop_configs).
            Booking::query()->delete();

            // Mitarbeitende löschen. submitted_by im Software-Katalog wird dabei
            // per nullOnDelete auf NULL gesetzt – die Katalog-Einträge bleiben.
            Employee::query()->delete();

            // Alle Zeitfenster wieder freigeben; manuell gesperrte (blocked)
            // bleiben gesperrt, nur ihr Zähler wird zurückgesetzt.
            TimeSlot::query()
                ->where('status', '!=', 'blocked')
                ->update(['status' => 'available', 'booked_count' => 0]);

            TimeSlot::query()
                ->where('status', 'blocked')
                ->update(['booked_count' => 0]);

            return $counts;
        });
    }

    /**
     * Zeitfenster belegen: Zähler erhöhen, bei Erreichen der Kapazität sperren.
     */
    private function occupy(TimeSlot $slot): void
    {
        $slot->booked_count++;

        if ($slot->booked_count >= $slot->capacity) {
            $slot->status = 'booked';
        }

        $slot->save();
    }

    /**
     * Zeitfenster freigeben: Zähler verringern, unter Kapazität wieder „frei“.
     */
    private function free(TimeSlot $slot): void
    {
        $slot->booked_count = max(0, $slot->booked_count - 1);

        if ($slot->booked_count < $slot->capacity) {
            $slot->status = 'available';
        }

        $slot->save();
    }
}
