<?php

namespace App\Filament\Resources\ProductionRecords\Pages;

use App\Filament\Resources\ProductionRecords\ProductionRecordResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListProductionRecords extends ListRecords
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
