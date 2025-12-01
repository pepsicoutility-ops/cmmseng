<?php

namespace App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages;

use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Chiller1ChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChiller1Checklist extends EditRecord
{
    protected static string $resource = Chiller1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
