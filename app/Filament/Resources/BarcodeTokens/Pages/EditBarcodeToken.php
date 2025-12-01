<?php

namespace App\Filament\Resources\BarcodeTokens\Pages;

use App\Filament\Resources\BarcodeTokens\BarcodeTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBarcodeToken extends EditRecord
{
    protected static string $resource = BarcodeTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
