<?php

namespace App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Pages;

use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Chiller2ChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChiller2Checklist extends EditRecord
{
    protected static string $resource = Chiller2ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
