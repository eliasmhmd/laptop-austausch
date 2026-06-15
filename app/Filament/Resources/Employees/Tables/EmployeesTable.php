<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Services\BookingManager;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nachname')
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
}
