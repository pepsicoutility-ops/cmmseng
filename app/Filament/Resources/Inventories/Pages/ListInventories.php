<?php

namespace App\Filament\Resources\Inventories\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
