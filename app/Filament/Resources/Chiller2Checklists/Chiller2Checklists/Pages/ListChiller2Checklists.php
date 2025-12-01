<?php

namespace App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Pages;

use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Chiller2ChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChiller2Checklists extends ListRecords
{
    protected static string $resource = Chiller2ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
