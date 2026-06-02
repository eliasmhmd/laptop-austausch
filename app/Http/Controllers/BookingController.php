<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Kalenderansicht: alle Zeitfenster nach Kalenderwoche und Tag gruppiert.
     * Optionaler Filter ?kw=NN. Pro Mitarbeiter:in ist nur ein aktiver Termin
     * erlaubt – wer bereits gebucht hat, wird zur Bestätigung weitergeleitet.
     */
    public function calendar(Request $request): View|RedirectResponse
    {
        $employee = $request->user('employee');

        if ($active = $employee->activeBookings()->first()) {
            return redirect()->route('booking.show', $active);
        }

        $selectedKw = $request->integer('kw') ?: null;

        $slots = TimeSlot::query()
            ->when($selectedKw, fn ($q) => $q->where('calendar_week', $selectedKw))
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        $calendar = $slots->groupBy([
            'calendar_week',
            fn (TimeSlot $slot): string => $slot->slot_date->format('Y-m-d'),
        ]);

        $weeks = TimeSlot::query()->distinct()->orderBy('calendar_week')->pluck('calendar_week');

        return view('booking.calendar', [
            'calendar' => $calendar,
            'weeks' => $weeks,
            'selectedKw' => $selectedKw,
            'availableIds' => $slots->filter->isAvailable()->pluck('id')->values(),
        ]);
    }

    /**
     * Bestätigungsformular für ein ausgewähltes Zeitfenster.
     */
    public function create(Request $request, TimeSlot $timeSlot): View|RedirectResponse
    {
        $employee = $request->user('employee');

        if ($employee->activeBookings()->exists()) {
            return redirect()->route('dashboard')
                ->with('status', 'Sie haben bereits einen Termin gebucht.');
        }

        if (! $timeSlot->isAvailable()) {
            return redirect()->route('booking.calendar')
                ->withErrors(['slot' => 'Dieses Zeitfenster ist leider nicht mehr verfügbar.']);
        }

        return view('booking.create', [
            'slot' => $timeSlot,
            'employee' => $employee,
        ]);
    }

    /**
     * Buchung speichern. Die eigentliche Doppelbuchungs-Sperre passiert in einer
     * Transaktion mit Zeilensperre (lockForUpdate) auf dem Zeitfenster.
     */
    public function store(Request $request, TimeSlot $timeSlot): RedirectResponse
    {
        $employee = $request->user('employee');

        $booking = DB::transaction(function () use ($employee, $timeSlot): ?Booking {
            // Nur ein aktiver Termin pro Mitarbeiter:in.
            if ($employee->activeBookings()->lockForUpdate()->exists()) {
                return null;
            }

            // Zeitfenster sperren und erneut prüfen, ob es noch frei ist.
            $slot = TimeSlot::whereKey($timeSlot->id)->lockForUpdate()->first();

            if (! $slot || ! $slot->isAvailable()) {
                return null;
            }

            $booking = Booking::create([
                'employee_id' => $employee->id,
                'time_slot_id' => $slot->id,
                'status' => 'confirmed',
                'booked_at' => now(),
            ]);

            $slot->booked_count++;

            if ($slot->booked_count >= $slot->capacity) {
                $slot->status = 'booked';
            }

            $slot->save();

            return $booking;
        });

        if (! $booking) {
            return redirect()->route('booking.calendar')->withErrors([
                'slot' => 'Dieses Zeitfenster ist leider nicht mehr verfügbar. Bitte wählen Sie ein anderes.',
            ]);
        }

        return redirect()->route('booking.show', $booking)
            ->with('status', 'Ihr Termin wurde erfolgreich gebucht.');
    }

    /**
     * Bestätigungs-/Detailseite einer Buchung (nur eigene Buchung sichtbar).
     */
    public function show(Request $request, Booking $booking): View
    {
        abort_unless($booking->employee_id === $request->user('employee')->id, 403);

        $booking->load('timeSlot', 'employee');

        return view('booking.show', ['booking' => $booking]);
    }

    /**
     * JSON: IDs aller aktuell verfügbaren Zeitfenster (für die Live-Aktualisierung
     * der Kalenderfarben via Alpine.js).
     */
    public function availability(Request $request): JsonResponse
    {
        $available = TimeSlot::query()
            ->where('status', 'available')
            ->whereColumn('booked_count', '<', 'capacity')
            ->when($request->integer('kw') ?: null, fn ($q, $kw) => $q->where('calendar_week', $kw))
            ->pluck('id');

        return response()->json(['available' => $available]);
    }

    /**
     * JSON: Ist genau dieses Zeitfenster noch frei? (Prüfung beim Anklicken.)
     */
    public function slotCheck(TimeSlot $timeSlot): JsonResponse
    {
        return response()->json(['available' => $timeSlot->isAvailable()]);
    }
}
