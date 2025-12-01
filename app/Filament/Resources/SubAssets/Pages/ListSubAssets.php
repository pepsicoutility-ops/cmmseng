<?php

namespace App\Filament\Resources\SubAssets\Pages;

use App\Filament\Resources\SubAssets\SubAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubAssets extends ListRecords
{
    protected static string $resource = SubAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
