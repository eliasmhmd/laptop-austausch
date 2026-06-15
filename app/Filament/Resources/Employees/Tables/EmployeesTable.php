<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Services\BookingManager;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nachname')
            // „hat einen Termin?“ als Unterabfrage mitladen (für Spalte + Aktion).
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withExists([
                'bookings as has_termin' => fn (Builder $q) => $q->whereIn('status', Employee::SETTLED_BOOKING_STATUSES),
            ]))
            ->columns([
                TextColumn::make('kvgg_nummer')
                    ->label('KVGG-Nr.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nachname')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, Employee $record): string => trim($record->vorname.' '.$record->nachname))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('abteilung')
                    ->label('Abteilung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pc_nummer')
                    ->label('PC-Nr.')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('has_termin')
                    ->label('Termin')
                    ->badge()
                    ->state(fn (Employee $record): string => $record->has_termin ? 'Gebucht' : 'Offen')
                    ->color(fn (Employee $record): string => $record->has_termin ? 'success' : 'danger'),
                TextColumn::make('bookings_count')
                    ->label('Buchungen')
                    ->counts('bookings')
                    ->toggleable(),
                TextColumn::make('last_laptop_exchange')
                    ->label('Letzter Austausch')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('abteilung')
                    ->label('Abteilung')
                    ->options(fn (): array => Employee::query()
                        ->whereNotNull('abteilung')
                        ->distinct()
                        ->orderBy('abteilung')
                        ->pluck('abteilung', 'abteilung')
                        ->all()),
                Filter::make('ohne_termin')
                    ->label('Nur ohne Termin')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->ohneTermin()),
            ])
            ->recordActions([
                ViewAction::make(),
                // Erinnerungs-E-Mail im Mailprogramm der/des Admins öffnen
                // (der Server selbst versendet keine Mails). Nur für Personen
                // ohne Termin und mit hinterlegter E-Mail-Adresse.
                Action::make('remind')
                    ->label('Erinnern')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('warning')
                    ->visible(fn (Employee $record): bool => filled($record->email) && ! $record->has_termin)
                    ->url(fn (Employee $record): string => self::reminderMailto($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Löschen')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Mitarbeitende löschen')
                        ->modalDescription('Die ausgewählten Mitarbeitenden werden mit allen ihren Buchungen unwiderruflich gelöscht. Belegte Zeitfenster werden wieder freigegeben.')
                        ->modalSubmitActionLabel('Endgültig löschen')
                        ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                        ->action(function (Collection $records): void {
                            // Erst die Slots freigeben, dann löschen – der DB-Cascade
                            // entfernt die Buchungen, setzt aber die Slot-Zähler nicht zurück.
                            app(BookingManager::class)->releaseSlotsForEmployees($records);

                            $count = $records->count();
                            $records->each(fn (Employee $employee) => $employee->delete());

                            Notification::make()
                                ->success()
                                ->title($count === 1 ? '1 Mitarbeiter:in gelöscht' : $count.' Mitarbeitende gelöscht')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * Baut einen mailto:-Link mit vorbefülltem Betreff und deutschem
     * Erinnerungstext. Der Versand erfolgt über das Mailprogramm der/des
     * Admins – der Server hat keinen Mailzugang.
     */
    public static function reminderMailto(Employee $employee): string
    {
        $subject = 'Erinnerung: Bitte Termin für den Laptop-Austausch auswählen';

        $body = "Guten Tag {$employee->vorname} {$employee->nachname},\r\n\r\n"
            .'Sie haben noch keinen Termin für den Austausch Ihres Laptops ausgewählt. '
            ."Bitte wählen Sie zeitnah einen freien Termin im Buchungstool aus.\r\n\r\n"
            ."Vielen Dank und freundliche Grüße\r\n"
            .'Ihre IT-Abteilung – Kreis Groß-Gerau';

        return 'mailto:'.$employee->email
            .'?subject='.rawurlencode($subject)
            .'&body='.rawurlencode($body);
    }
}
