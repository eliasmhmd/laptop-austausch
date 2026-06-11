<?php

namespace App\Filament\Resources\SoftwareCatalogs\Schemas;

use App\Models\SoftwareCatalog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SoftwareCatalogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === SoftwareCatalog::STATUS_PENDING ? 'Wartet auf Freigabe' : 'Freigegeben')
                            ->color(fn (string $state): string => $state === SoftwareCatalog::STATUS_PENDING ? 'warning' : 'success'),
                        TextEntry::make('version')->label('Version')->placeholder('—'),
                        TextEntry::make('publisher')->label('Hersteller')->placeholder('—'),
                        IconEntry::make('is_standard')
                            ->label('Standardsoftware')
                            ->boolean(),
                        TextEntry::make('submittedBy.full_name')
                            ->label('Eingereicht von')
                            ->state(fn (SoftwareCatalog $record): string => $record->submittedBy
                                ? trim($record->submittedBy->vorname.' '.$record->submittedBy->nachname)
                                : '—'),
                    ]),
            ]);
    }
}
