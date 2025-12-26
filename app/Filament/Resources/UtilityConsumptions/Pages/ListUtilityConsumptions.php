<?php

namespace App\Filament\Resources\UtilityConsumptions\Pages;

use App\Filament\Resources\UtilityConsumptions\UtilityConsumptionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListUtilityConsumptions extends ListRecords
{
    protected static string $resource = UtilityConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
