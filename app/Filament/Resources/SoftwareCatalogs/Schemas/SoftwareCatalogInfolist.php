<?php

namespace App\Filament\Resources\SoftwareCatalogs\Schemas;

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
                        TextEntry::make('version')->label('Version')->placeholder('—'),
                        TextEntry::make('publisher')->label('Hersteller')->placeholder('—'),
                        IconEntry::make('is_standard')
                            ->label('Standardsoftware')
                            ->boolean(),
                    ]),
            ]);
    }
}
