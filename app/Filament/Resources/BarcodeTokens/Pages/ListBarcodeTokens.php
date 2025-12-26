<?php

namespace App\Filament\Resources\BarcodeTokens\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BarcodeTokens\BarcodeTokenResource;
use Filament\Resources\Pages\ListRecords;

class ListBarcodeTokens extends ListRecords
{
    protected static string $resource = BarcodeTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
