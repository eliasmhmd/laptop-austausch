<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\TimeSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Buchung';

    protected static ?string $pluralModelLabel = 'Buchungen';

    protected static ?string $navigationLabel = 'Buchungen';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }

    /**
     * Auswahlliste der Mitarbeitenden für manuelle Buchungen
     * (Nachname, Vorname – KVGG-Nr.), nach Nachname sortiert.
     *
     * @return array<int, string>
     */
    public static function employeeOptions(): array
    {
        return Employee::query()
            ->orderBy('nachname')
            ->orderBy('vorname')
            ->get()
            ->mapWithKeys(fn (Employee $e): array => [
                $e->id => trim($e->nachname.', '.$e->vorname).' – '.$e->kvgg_nummer,
            ])
            ->all();
    }

    /**
     * Auswahlliste der aktuell freien Zeitfenster (optional eines ausschließen,
     * z. B. das bereits gebuchte beim Verschieben).
     *
     * @return array<int, string>
     */
    public static function availableSlotOptions(?int $excludeId = null): array
    {
        return TimeSlot::query()
            ->where('status', 'available')
            ->whereColumn('booked_count', '<', 'capacity')
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get()
            ->mapWithKeys(fn (TimeSlot $slot): array => [
                $slot->id => Carbon::parse($slot->slot_date)->translatedFormat('D d.m.Y')
                    .', '.substr($slot->start_time, 0, 5).' Uhr (KW '.$slot->calendar_week.')',
            ])
            ->all();
    }
}
