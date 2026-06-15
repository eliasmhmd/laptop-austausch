<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Models\BookingSoftware;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

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
                                ->map(fn ($sw): string => self::softwareLabel($sw))
                                ->filter()
                                ->implode(', '))
                            ->placeholder('Keine Angaben')
                            ->hintAction(
                                Action::make('removeSoftware')
                                    ->label('Bearbeiten')
                                    ->icon(Heroicon::OutlinedPencilSquare)
                                    ->visible(fn (Booking $record): bool => self::userIsAdmin() && $record->software->isNotEmpty())
                                    ->modalHeading('Software entfernen')
                                    ->modalDescription('Wählen Sie die Einträge, die von dieser Buchung entfernt werden sollen.')
                                    ->modalSubmitActionLabel('Entfernen')
                                    ->schema([
                                        CheckboxList::make('remove')
                                            ->label('Zu entfernende Software')
                                            ->options(fn (Booking $record): array => $record->software
                                                ->mapWithKeys(fn (BookingSoftware $sw): array => [$sw->id => self::softwareLabel($sw)])
                                                ->all())
                                            ->required(),
                                    ])
                                    ->action(function (array $data, Booking $record): void {
                                        $ids = $data['remove'] ?? [];

                                        $deleted = BookingSoftware::query()
                                            ->where('booking_id', $record->id)
                                            ->whereIn('id', $ids)
                                            ->delete();

                                        // Relation neu laden, damit die Anzeige sofort stimmt.
                                        $record->load('software.softwareCatalog');

                                        Notification::make()
                                            ->success()
                                            ->title($deleted === 1 ? '1 Software entfernt' : $deleted.' Einträge entfernt')
                                            ->send();
                                    }),
                            ),
                        TextEntry::make('laptopConfig.additional_notes')
                            ->label('Zusätzliche Angaben')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }

    /**
     * Anzeigename eines Software-Eintrags einer Buchung – Sonderwünsche werden
     * mit „(Spezial)“ gekennzeichnet.
     */
    private static function softwareLabel(BookingSoftware $sw): string
    {
        return $sw->is_custom
            ? trim((string) $sw->custom_software_name).' (Spezial)'
            : ($sw->softwareCatalog?->name ?? '');
    }

    /**
     * Nur Admins dürfen Software aus einer Buchung entfernen.
     */
    private static function userIsAdmin(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }
}
