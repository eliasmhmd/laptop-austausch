<?php

namespace App\Filament\Resources\SoftwareCatalogs\Pages;

use App\Filament\Resources\SoftwareCatalogs\SoftwareCatalogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareCatalog extends ViewRecord
{
    protected static string $resource = SoftwareCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
