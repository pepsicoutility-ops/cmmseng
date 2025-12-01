<?php

namespace App\Filament\Resources\SubAreas\Pages;

use App\Filament\Resources\SubAreas\SubAreaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubArea extends ViewRecord
{
    protected static string $resource = SubAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
