<?php

namespace App\Filament\Resources\SoftwareCatalogs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SoftwareCatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->label('Version')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('publisher')
                    ->label('Hersteller')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_standard')
                    ->label('Standard')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('booking_software_count')
                    ->label('Verwendet')
                    ->counts('bookingSoftware')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_standard')
                    ->label('Standardsoftware')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Standardsoftware')
                    ->falseLabel('Nur Zusatzsoftware'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
