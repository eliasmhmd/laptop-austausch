<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mitarbeiter:in')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Name')
                            ->state(fn (Booking $record): string => trim($record->employee?->vorname.' '.$record->employee?->nachname)),
                        TextEntry::make('employee.kvgg_nummer')->label('KVGG-Nr.'),
                        TextEntry::make('employee.email')->label('E-Mail'),
                        TextEntry::make('employee.abteilung')->label('Abteilung'),
                    ]),

                Section::make('Termin')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('timeSlot.slot_date')->label('Datum')->date('l, d.m.Y'),
                        TextEntry::make('timeSlot.start_time')
                            ->label('Uhrzeit')
                            ->state(fn (Booking $record): string => $record->timeSlot
                                ? substr($record->timeSlot->start_time, 0, 5).'–'.substr($record->timeSlot->end_time, 0, 5).' Uhr'
                                : '—'),
                        TextEntry::make('timeSlot.calendar_week')->label('Kalenderwoche')->formatStateUsing(fn ($state): string => "KW $state"),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => BookingsTable::STATUS_LABELS[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'completed' => 'info',
                                'no_show' => 'danger',
                                'sick' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('booked_at')->label('Gebucht am')->dateTime('d.m.Y H:i'),
                    ]),

                Section::make('Status-Details')
                    ->schema([
                        TextEntry::make('cancellation_reason')->label('Stornogrund')->placeholder('—'),
                        TextEntry::make('unplanned_note')->label('Notiz (No-Show / Krank)')->placeholder('—'),
                    ])
                    ->visible(fn (Booking $record): bool => filled($record->cancellation_reason) || filled($record->unplanned_note)),

                Section::make('Laptop & Software')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('laptopConfig.old_manufacturer')->label('Hersteller (Altgerät)')->placeholder('—'),
                        TextEntry::make('laptopConfig.old_pc_nummer')->label('PC-Nummer (Altgerät)')->placeholder('—'),
                        TextEntry::make('software')
                            ->label('Software für Neuinstallation')
                            ->columnSpanFull()
                            ->state(fn (Booking $record): string => $record->software
                                ->map(fn ($sw): string => $sw->is_custom ? $sw->custom_software_name.' (Spezial)' : ($sw->softwareCatalog?->name ?? ''))
                                ->filter()
                                ->implode(', '))
                            ->placeholder('Keine Angaben'),
                    ]),
            ]);
    }
}
