<?php

namespace App\Filament\Resources\TimeSlots\Tables;

use App\Models\TimeSlot;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TimeSlotsTable
{
    /**
     * Deutsche Beschriftungen für die Slot-Status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'available' => 'frei',
        'booked' => 'gebucht',
        'blocked' => 'gesperrt',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('slot_date')
            ->columns([
                TextColumn::make('slot_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('slot_date')
                    ->label('Wochentag')
                    ->formatStateUsing(fn ($state): string => $state?->translatedFormat('l') ?? '—'),
                TextColumn::make('start_time')
                    ->label('Uhrzeit')
                    ->formatStateUsing(fn (?string $state, TimeSlot $record): string => $state
                        ? substr($state, 0, 5).'–'.substr($record->end_time, 0, 5)
                        : '—'),
                TextColumn::make('calendar_week')
                    ->label('KW')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'booked' => 'danger',
                        'blocked' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('booked_count')
                    ->label('Belegung')
                    ->formatStateUsing(fn ($state, TimeSlot $record): string => "$state / $record->capacity"),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::STATUS_LABELS),
                SelectFilter::make('calendar_week')
                    ->label('Kalenderwoche')
                    ->options(fn (): array => TimeSlot::query()
                        ->distinct()
                        ->orderBy('calendar_week')
                        ->pluck('calendar_week', 'calendar_week')
                        ->mapWithKeys(fn ($kw): array => [$kw => "KW $kw"])
                        ->all()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
