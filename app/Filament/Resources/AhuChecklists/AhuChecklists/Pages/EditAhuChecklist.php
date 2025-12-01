<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists\Pages;

use App\Filament\Resources\AhuChecklists\AhuChecklists\AhuChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAhuChecklist extends EditRecord
{
    protected static string $resource = AhuChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
