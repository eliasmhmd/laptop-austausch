<?php

namespace App\Filament\Resources\SoftwareCatalogs\Pages;

use App\Filament\Resources\SoftwareCatalogs\SoftwareCatalogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareCatalog extends EditRecord
{
    protected static string $resource = SoftwareCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
