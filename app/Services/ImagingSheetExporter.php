<?php

namespace App\Services;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Models\SoftwareCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Erzeugt das druckbare „Imaging-Blatt“ (PDF): die Checkliste, die die IT vor
 * den neuen Laptop legt, um die richtige Software aufzuspielen. Ein Blatt pro
 * Buchung; im Tagesdruck ein Blatt je Termin.
 */
class ImagingSheetExporter
{
    /**
     * Eager-Loads, die ein Blatt benötigt.
     *
     * @var list<string>
     */
    private const RELATIONS = ['employee', 'timeSlot', 'laptopConfig', 'software.softwareCatalog'];

    /**
     * PDF für eine einzelne Buchung.
     */
    public function pdfForBooking(Booking $booking): string
    {
        $booking->loadMissing(self::RELATIONS);

        return $this->render(collect([$booking]));
    }

    /**
     * Alle nicht stornierten Buchungen eines Tages (nach Uhrzeit sortiert).
     *
     * @return Collection<int, Booking>
     */
    public function bookingsForDate(string $date): Collection
    {
        return Booking::query()
            ->whereHas('timeSlot', fn ($q) => $q->whereDate('slot_date', $date))
            ->where('status', '!=', 'cancelled')
            ->with(self::RELATIONS)
            ->get()
            ->sortBy(fn (Booking $b): string => (string) $b->timeSlot?->start_time)
            ->values();
    }

    /**
     * PDF mit einem Blatt je Termin eines Tages.
     */
    public function pdfForDate(string $date): string
    {
        return $this->render($this->bookingsForDate($date));
    }

    /**
     * Aufbereitete Daten für genau ein Blatt – getrennt testbar.
     *
     * @return array<string, mixed>
     */
    public function sheetData(Booking $booking): array
    {
        $booking->loadMissing(self::RELATIONS);

        $employee = $booking->employee;
        $slot = $booking->timeSlot;

        // Standardsoftware (auf jedem Laptop) aus dem Katalog.
        $standard = SoftwareCatalog::query()
            ->where('is_standard', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        // Zusätzlich angefordert: Sonderwünsche + nicht-standardmäßige Katalog-
        // einträge dieser Buchung (Standardsoftware steht ja schon oben).
        $requested = $booking->software
            ->map(function ($sw): ?string {
                if ($sw->is_custom) {
                    return trim((string) $sw->custom_software_name).' (Spezial)';
                }

                $catalog = $sw->softwareCatalog;

                if (! $catalog || $catalog->is_standard) {
                    return null;
                }

                return $catalog->name;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'name' => trim(($employee?->vorname ?? '').' '.($employee?->nachname ?? '')) ?: '—',
            'kvgg' => $employee?->kvgg_nummer ?? '—',
            'abteilung' => $employee?->abteilung ?? '—',
            'date' => $slot ? Carbon::parse($slot->slot_date)->translatedFormat('l, d.m.Y') : '—',
            'time' => $slot ? substr($slot->start_time, 0, 5).'–'.substr($slot->end_time, 0, 5).' Uhr' : '—',
            'kw' => $slot?->calendar_week,
            'status' => BookingsTable::STATUS_LABELS[$booking->status] ?? $booking->status,
            'old_pc' => $booking->laptopConfig?->old_pc_nummer ?: ($employee?->pc_nummer ?? '—'),
            'standard' => $standard,
            'requested' => $requested,
        ];
    }

    /**
     * Sammlung von Buchungen in ein mehrseitiges PDF rendern.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    private function render(Collection $bookings): string
    {
        $sheets = $bookings->map(fn (Booking $b): array => $this->sheetData($b))->all();

        return Pdf::loadView('admin.imaging-sheet', ['sheets' => $sheets])
            ->setPaper('a4')
            ->output();
    }
}
