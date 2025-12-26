<?php

namespace App\Filament\Resources\KaizenResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\KaizenResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKaizen extends ViewRecord
{
    protected static string $resource = KaizenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
