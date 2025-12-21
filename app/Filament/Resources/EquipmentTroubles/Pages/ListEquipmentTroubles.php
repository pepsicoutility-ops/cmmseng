<?php

namespace App\Filament\Resources\EquipmentTroubles\Pages;

use App\Filament\Resources\EquipmentTroubles\EquipmentTroubleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentTroubles extends ListRecords
{
    protected static string $resource = EquipmentTroubleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
