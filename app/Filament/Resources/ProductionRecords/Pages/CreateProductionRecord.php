<?php

namespace App\Filament\Resources\ProductionRecords\Pages;

use App\Filament\Resources\ProductionRecords\ProductionRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionRecord extends CreateRecord
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
