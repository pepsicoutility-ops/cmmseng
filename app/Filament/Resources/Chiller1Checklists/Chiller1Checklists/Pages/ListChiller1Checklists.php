<?php

namespace App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages;

use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Chiller1ChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChiller1Checklists extends ListRecords
{
    protected static string $resource = Chiller1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
