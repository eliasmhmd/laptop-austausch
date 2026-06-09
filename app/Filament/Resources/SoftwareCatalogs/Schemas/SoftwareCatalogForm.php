<?php

namespace App\Filament\Resources\SoftwareCatalogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SoftwareCatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Eindeutiger Programmname, z. B. „Adobe Acrobat Reader“.'),
                TextInput::make('version')
                    ->label('Version (optional)')
                    ->maxLength(255),
                TextInput::make('publisher')
                    ->label('Hersteller (optional)')
                    ->maxLength(255),
                Toggle::make('is_standard')
                    ->label('Standardsoftware (auf jedem Laptop)')
                    ->helperText('Wird auf jedem Imaging-Blatt automatisch aufgeführt.')
                    ->default(false),
            ]);
    }
}
