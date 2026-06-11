<?php

namespace App\Filament\Resources\SoftwareCatalogs\Pages;

use App\Filament\Resources\SoftwareCatalogs\SoftwareCatalogResource;
use App\Models\SoftwareCatalog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSoftwareCatalogs extends ListRecords
{
    protected static string $resource = SoftwareCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $pendingCount = SoftwareCatalog::query()->pending()->count();

        return [
            'all' => Tab::make('Alle'),
            'pending' => Tab::make('Wartet auf Freigabe')
                ->badge($pendingCount > 0 ? $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()),
            'approved' => Tab::make('Freigegeben')
                ->modifyQueryUsing(fn (Builder $query) => $query->approved()),
        ];
    }
}
