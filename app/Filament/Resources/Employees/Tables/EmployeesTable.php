<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ->toolbarActions([]);
    }
}
