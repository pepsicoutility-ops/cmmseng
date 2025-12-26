<?php

namespace App\Filament\Resources\ProductionRecords\Pages;

use App\Filament\Resources\ProductionRecords\ProductionRecordResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditProductionRecord extends EditRecord
{
    protected static string $resource = ProductionRecordResource::class;

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
