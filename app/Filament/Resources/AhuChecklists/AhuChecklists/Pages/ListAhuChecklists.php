<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists\Pages;

use App\Filament\Resources\AhuChecklists\AhuChecklists\AhuChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAhuChecklists extends ListRecords
{
    protected static string $resource = AhuChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
