<?php

namespace App\Filament\Resources\Compressor2Checklists\Pages;

use App\Filament\Resources\Compressor2Checklists\Compressor2ChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompressor2Checklist extends EditRecord
{
    protected static string $resource = Compressor2ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
