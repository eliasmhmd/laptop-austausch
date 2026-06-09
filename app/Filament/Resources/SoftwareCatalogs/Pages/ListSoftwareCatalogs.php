<?php

namespace App\Filament\Resources\SoftwareCatalogs\Pages;

use App\Filament\Resources\SoftwareCatalogs\SoftwareCatalogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareCatalogs extends ListRecords
{
    protected static string $resource = SoftwareCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
