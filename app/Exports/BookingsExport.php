<?php

namespace App\Exports;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Excel-Export der Buchungsübersicht. Bekommt die bereits gefilterte/sortierte
 * Tabellen-Query aus dem Filament-Panel übergeben, sodass der Export genau das
 * enthält, was in der Liste sichtbar ist.
 *
 * @implements WithMapping<Booking>
 */
class BookingsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with(['employee', 'timeSlot']);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'KVGG-Nr.',
            'Name',
            'Abteilung',
            'E-Mail',
            'Datum',
            'Uhrzeit',
            'KW',
            'Status',
            'Gebucht am',
        ];
    }

    /**
     * @param  Booking  $booking
     * @return list<string>
     */
    public function map($booking): array
    {
        $slot = $booking->timeSlot;

        return [
            $booking->employee?->kvgg_nummer ?? '',
            trim(($booking->employee?->vorname ?? '').' '.($booking->employee?->nachname ?? '')),
            $booking->employee?->abteilung ?? '',
            $booking->employee?->email ?? '',
            $slot ? Carbon::parse($slot->slot_date)->format('d.m.Y') : '',
            $slot ? substr($slot->start_time, 0, 5).'–'.substr($slot->end_time, 0, 5) : '',
            $slot?->calendar_week ? 'KW '.$slot->calendar_week : '',
            BookingsTable::STATUS_LABELS[$booking->status] ?? $booking->status,
            $booking->booked_at ? $booking->booked_at->format('d.m.Y H:i') : '',
        ];
    }
}
