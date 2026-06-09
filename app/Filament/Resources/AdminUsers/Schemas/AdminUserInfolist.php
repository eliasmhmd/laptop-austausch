<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminUserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Admin-Konto')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('email')->label('E-Mail'),
                        TextEntry::make('role')
                            ->label('Rolle')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'admin' ? 'Administrator' : 'Betrachter')
                            ->color(fn (string $state): string => $state === 'admin' ? 'success' : 'gray'),
                        TextEntry::make('created_at')->label('Erstellt am')->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
