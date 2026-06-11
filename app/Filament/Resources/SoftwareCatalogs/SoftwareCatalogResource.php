<?php

namespace App\Filament\Resources\SoftwareCatalogs;

use App\Filament\Resources\SoftwareCatalogs\Pages\CreateSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\Pages\EditSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\Pages\ListSoftwareCatalogs;
use App\Filament\Resources\SoftwareCatalogs\Pages\ViewSoftwareCatalog;
use App\Filament\Resources\SoftwareCatalogs\RelationManagers\UsageRelationManager;
use App\Filament\Resources\SoftwareCatalogs\Schemas\SoftwareCatalogForm;
use App\Filament\Resources\SoftwareCatalogs\Schemas\SoftwareCatalogInfolist;
use App\Filament\Resources\SoftwareCatalogs\Tables\SoftwareCatalogsTable;
use App\Models\SoftwareCatalog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SoftwareCatalogResource extends Resource
{
    protected static ?string $model = SoftwareCatalog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Software';

    protected static ?string $pluralModelLabel = 'Software-Katalog';

    protected static ?string $navigationLabel = 'Software-Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SoftwareCatalogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareCatalogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareCatalogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsageRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = SoftwareCatalog::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoftwareCatalogs::route('/'),
            'create' => CreateSoftwareCatalog::route('/create'),
            'view' => ViewSoftwareCatalog::route('/{record}'),
            'edit' => EditSoftwareCatalog::route('/{record}/edit'),
        ];
    }

    // Betrachter:innen dürfen den Katalog sehen, aber nicht ändern.

    protected static function isAdmin(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::isAdmin();
    }
}
