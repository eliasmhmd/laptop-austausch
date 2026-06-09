<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Models\AdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdminUsersTable
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
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rolle')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'admin' ? 'Administrator' : 'Betrachter')
                    ->color(fn (string $state): string => $state === 'admin' ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Eigenes Konto kann nicht gelöscht werden.
                DeleteAction::make()
                    ->visible(fn (AdminUser $record): bool => $record->id !== Auth::guard('admin')->id()),
            ])
            ->toolbarActions([]);
    }
}
