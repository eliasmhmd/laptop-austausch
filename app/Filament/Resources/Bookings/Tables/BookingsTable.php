<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use App\Services\BookingManager;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BookingsTable
{
    /**
     * Deutsche Beschriftungen für die Buchungsstatus.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'confirmed' => 'bestätigt',
        'cancelled' => 'storniert',
        'completed' => 'abgeschlossen',
        'no_show' => 'nicht erschienen',
        'sick' => 'krank',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['employee', 'timeSlot']))
            ->defaultSort('booked_at', 'desc')
            ->columns([
                TextColumn::make('employee.kvgg_nummer')
                    ->label('KVGG-Nr.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.nachname')
                    ->label('Mitarbeiter:in')
                    ->formatStateUsing(fn ($record): string => trim($record->employee?->vorname.' '.$record->employee?->nachname))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.abteilung')
                    ->label('Abteilung')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('timeSlot.slot_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('timeSlot.start_time')
                    ->label('Uhrzeit')
                    ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5).' Uhr' : '—'),
                TextColumn::make('timeSlot.calendar_week')
                    ->label('KW')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'no_show' => 'danger',
                        'sick' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('booked_at')
                    ->label('Gebucht am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::STATUS_LABELS),
                SelectFilter::make('calendar_week')
                    ->label('Kalenderwoche')
                    ->options(fn (): array => \App\Models\TimeSlot::query()
                        ->distinct()
                        ->orderBy('calendar_week')
                        ->pluck('calendar_week', 'calendar_week')
                        ->mapWithKeys(fn ($kw): array => [$kw => "KW $kw"])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, $kw): Builder => $q->whereHas('timeSlot', fn (Builder $s) => $s->where('calendar_week', $kw)),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Löschen')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Buchungen löschen')
                        ->modalDescription('Die ausgewählten Buchungen werden unwiderruflich gelöscht. Die zugehörigen Zeitfenster werden wieder freigegeben.')
                        ->modalSubmitActionLabel('Endgültig löschen')
                        ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                        ->action(function (Collection $records): void {
                            // Erst die Slots freigeben, dann löschen – der DB-Cascade
                            // entfernt die Buchungen, setzt aber die Slot-Zähler nicht zurück.
                            app(BookingManager::class)->releaseSlotsForBookings($records);

                            $count = $records->count();
                            $records->each(fn (Booking $booking) => $booking->delete());

                            Notification::make()
                                ->success()
                                ->title($count === 1 ? '1 Buchung gelöscht' : $count.' Buchungen gelöscht')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
