<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Resources\AdminUsers\Pages\ViewAdminUser;
use App\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use App\Filament\Resources\AdminUsers\Schemas\AdminUserInfolist;
use App\Filament\Resources\AdminUsers\Tables\AdminUsersTable;
use App\Models\AdminUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'Admin-Konto';

    protected static ?string $pluralModelLabel = 'Admin-Konten';

    protected static ?string $navigationLabel = 'Admin-Konten';

    protected static ?int $navigationSort = 9;

    /**
     * Nur Admins dürfen Konten verwalten. Gilt für Navigation und alle Seiten
     * dieser Resource – Betrachter sehen das Menü gar nicht erst.
     */
    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return AdminUserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdminUserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'view' => ViewAdminUser::route('/{record}'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
