<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Rolle')
                    ->required()
                    ->options([
                        'admin' => 'Administrator (Vollzugriff)',
                        'viewer' => 'Betrachter (nur Lesen)',
                    ])
                    ->default('viewer'),
                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->revealable()
                    // Beim Anlegen Pflicht, beim Bearbeiten nur bei Eingabe ändern.
                    // Das Hashing übernimmt der 'hashed'-Cast im AdminUser-Model.
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Beim Bearbeiten leer lassen, um das Passwort beizubehalten.')
                    ->maxLength(255),
            ]);
    }
}
