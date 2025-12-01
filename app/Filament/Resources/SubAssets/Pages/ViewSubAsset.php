<?php

namespace App\Filament\Resources\SubAssets\Pages;

use App\Filament\Resources\SubAssets\SubAssetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubAsset extends ViewRecord
{
    protected static string $resource = SubAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
