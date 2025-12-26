<?php

namespace App\Filament\Resources\UtilityConsumptions\Pages;

use App\Filament\Resources\UtilityConsumptions\UtilityConsumptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUtilityConsumption extends CreateRecord
{
    protected static string $resource = UtilityConsumptionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
