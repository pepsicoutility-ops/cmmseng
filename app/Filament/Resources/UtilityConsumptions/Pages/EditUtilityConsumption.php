<?php

namespace App\Filament\Resources\UtilityConsumptions\Pages;

use App\Filament\Resources\UtilityConsumptions\UtilityConsumptionResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditUtilityConsumption extends EditRecord
{
    protected static string $resource = UtilityConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
