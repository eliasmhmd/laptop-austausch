<?php

namespace App\Filament\Resources\SoftwareCatalogs\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\BookingSoftware;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Zeigt im Software-Katalog, welche Mitarbeiter:innen eine bestimmte Software
 * angefordert haben. Jede Zeile verlinkt auf die zugehörige Buchung.
 */
class UsageRelationManager extends RelationManager
{
    protected static string $relationship = 'bookingSoftware';

    protected static ?string $title = 'Verwendet von';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Verwendet von')
            ->modifyQueryUsing(fn ($query) => $query->with(['booking.employee', 'booking.timeSlot']))
            ->emptyStateHeading('Noch nicht angefordert')
            ->emptyStateDescription('Diese Software wurde bisher von niemandem ausgewählt.')
            ->columns([
                TextColumn::make('booking.employee.nachname')
                    ->label('Mitarbeiter:in')
                    ->state(fn (BookingSoftware $record): string => trim(
                        ($record->booking?->employee?->vorname ?? '').' '.($record->booking?->employee?->nachname ?? '')
                    ) ?: '—')
                    ->searchable(['vorname', 'nachname'])
                    ->sortable(),
                TextColumn::make('booking.employee.kvgg_nummer')
                    ->label('KVGG-Nr.')
                    ->placeholder('—'),
                TextColumn::make('booking.employee.abteilung')
                    ->label('Abteilung')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('booking.timeSlot.slot_date')
                    ->label('Termin')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('booking.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (BookingsTable::STATUS_LABELS[$state] ?? $state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'no_show' => 'danger',
                        'sick' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordUrl(fn (BookingSoftware $record): ?string => $record->booking_id
                ? BookingResource::getUrl('view', ['record' => $record->booking_id])
                : null);
    }
}
