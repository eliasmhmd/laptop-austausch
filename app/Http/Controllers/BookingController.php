<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsSlotCalendar;
use App\Models\Booking;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    use BuildsSlotCalendar;

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

        return view('booking.calendar', $this->slotCalendar($request->integer('kw') ?: null));
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

        // Direkt im Anschluss die Software erfassen; die Bestätigungsseite
        // („Termin gebucht") folgt nach dem Speichern des Formulars.
        return redirect()->route('config.edit', $booking)
            ->with('status', 'Ihr Termin wurde gebucht. Bitte erfassen Sie noch die Software Ihres aktuellen Geräts.');
    }

    /**
     * Bestätigungs-/Detailseite einer Buchung (nur eigene Buchung sichtbar).
     */
    public function show(Request $request, Booking $booking): View
    {
        abort_unless($booking->employee_id === $request->user('employee')->id, 403);

        $booking->load('timeSlot', 'employee', 'laptopConfig', 'software.softwareCatalog');

        return view('booking.show', ['booking' => $booking]);
    }

    /**
     * iCal-Datei (.ics) zum Download – kein E-Mail-Versand, nur Browser-Download.
     * Zeiten werden nach UTC konvertiert, damit Outlook & Co. sie korrekt in der
     * lokalen Zeitzone (Europe/Berlin) anzeigen.
     */
    public function ical(Request $request, Booking $booking): Response
    {
        abort_unless($booking->employee_id === $request->user('employee')->id, 403);

        $booking->load('timeSlot', 'employee');
        $slot = $booking->timeSlot;
        $date = $slot->slot_date->format('Y-m-d');

        $start = Carbon::parse("{$date} {$slot->start_time}", 'Europe/Berlin')->utc();
        $end = Carbon::parse("{$date} {$slot->end_time}", 'Europe/Berlin')->utc();
        $fmt = fn (Carbon $d): string => $d->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kreis Groß-Gerau//Laptop-Austausch//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:booking-'.$booking->id.'@laptop-austausch.kreisgg.de',
            'DTSTAMP:'.$fmt(now()->utc()),
            'DTSTART:'.$fmt($start),
            'DTEND:'.$fmt($end),
            'SUMMARY:'.$this->icalEscape('Laptop-Austausch'),
            'DESCRIPTION:'.$this->icalEscape('Austausch Ihres Laptops bei der IT-Abteilung. PC-Nummer: '.$booking->employee->pc_nummer),
            'LOCATION:'.$this->icalEscape('IT-Abteilung, Kreis Groß-Gerau'),
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $content = implode("\r\n", $lines)."\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="laptop-austausch-termin.ics"',
        ]);
    }

    /**
     * Sonderzeichen für iCal-Textfelder maskieren (RFC 5545).
     */
    private function icalEscape(string $text): string
    {
        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $text);
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
