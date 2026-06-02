<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsSlotCalendar;
use App\Models\Booking;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RescheduleController extends Controller
{
    use BuildsSlotCalendar;

    /**
     * Verschiebe-Ansicht: aktueller Termin + Kalender zur Auswahl eines neuen
     * Zeitfensters. Setzt eine bestehende (bestätigte) Buchung voraus.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $current = $request->user('employee')->activeBookings()->with('timeSlot')->first();

        if (! $current) {
            return redirect()->route('booking.calendar')
                ->with('status', 'Sie haben noch keinen Termin, den Sie verschieben könnten.');
        }

        return view('reschedule.edit', [
            ...$this->slotCalendar($request->integer('kw') ?: null),
            'current' => $current,
        ]);
    }

    /**
     * Bestätigungsseite: alter Termin -> neues Zeitfenster.
     */
    public function confirm(Request $request, TimeSlot $timeSlot): View|RedirectResponse
    {
        $employee = $request->user('employee');
        $current = $employee->activeBookings()->with('timeSlot')->first();

        if (! $current) {
            return redirect()->route('booking.calendar');
        }

        if ($timeSlot->id === $current->time_slot_id) {
            return redirect()->route('reschedule.edit')
                ->withErrors(['slot' => 'Das ist bereits Ihr aktueller Termin.']);
        }

        if (! $timeSlot->isAvailable()) {
            return redirect()->route('reschedule.edit')
                ->withErrors(['slot' => 'Dieses Zeitfenster ist leider nicht mehr verfügbar.']);
        }

        return view('reschedule.confirm', [
            'current' => $current,
            'slot' => $timeSlot,
            'employee' => $employee,
        ]);
    }

    /**
     * Verschieben durchführen: alte Buchung stornieren + altes Fenster freigeben,
     * neue Buchung anlegen + neues Fenster belegen – alles in einer Transaktion
     * mit Zeilensperren, damit dabei keine Doppelbuchung entsteht.
     */
    public function update(Request $request, TimeSlot $timeSlot): RedirectResponse
    {
        $employee = $request->user('employee');

        $booking = DB::transaction(function () use ($employee, $timeSlot): ?Booking {
            $current = $employee->activeBookings()->lockForUpdate()->first();

            if (! $current) {
                return null;
            }

            $newSlot = TimeSlot::whereKey($timeSlot->id)->lockForUpdate()->first();

            if (! $newSlot || $newSlot->id === $current->time_slot_id || ! $newSlot->isAvailable()) {
                return null;
            }

            // Alten Termin stornieren und altes Zeitfenster wieder freigeben.
            $oldSlot = TimeSlot::whereKey($current->time_slot_id)->lockForUpdate()->first();

            $current->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Vom Mitarbeiter verschoben',
            ]);

            if ($oldSlot) {
                $oldSlot->booked_count = max(0, $oldSlot->booked_count - 1);

                if ($oldSlot->booked_count < $oldSlot->capacity) {
                    $oldSlot->status = 'available';
                }

                $oldSlot->save();
            }

            // Neuen Termin anlegen und neues Zeitfenster belegen.
            $booking = Booking::create([
                'employee_id' => $employee->id,
                'time_slot_id' => $newSlot->id,
                'status' => 'confirmed',
                'booked_at' => now(),
            ]);

            $newSlot->booked_count++;

            if ($newSlot->booked_count >= $newSlot->capacity) {
                $newSlot->status = 'booked';
            }

            $newSlot->save();

            return $booking;
        });

        if (! $booking) {
            return redirect()->route('reschedule.edit')->withErrors([
                'slot' => 'Dieses Zeitfenster ist leider nicht mehr verfügbar. Bitte wählen Sie ein anderes.',
            ]);
        }

        return redirect()->route('booking.show', $booking)
            ->with('status', 'Ihr Termin wurde erfolgreich verschoben.');
    }
}
