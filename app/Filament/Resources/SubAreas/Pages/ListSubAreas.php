<?php

namespace App\Filament\Resources\SubAreas\Pages;

use App\Filament\Resources\SubAreas\SubAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubAreas extends ListRecords
{
    protected static string $resource = SubAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
