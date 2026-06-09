<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mitarbeiter:in')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Name')
                            ->state(fn (Employee $record): string => trim($record->vorname.' '.$record->nachname)),
                        TextEntry::make('kvgg_nummer')->label('KVGG-Nr.'),
                        TextEntry::make('email')->label('E-Mail'),
                        TextEntry::make('abteilung')->label('Abteilung'),
                        TextEntry::make('pc_nummer')->label('PC-Nr.'),
                        TextEntry::make('last_laptop_exchange')->label('Letzter Austausch')->date('d.m.Y')->placeholder('—'),
                    ]),
            ]);
    }
}
