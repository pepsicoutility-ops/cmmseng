<?php

namespace App\Filament\Resources\Compressor1Checklists\Pages;

use App\Filament\Resources\Compressor1Checklists\Compressor1ChecklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompressor1Checklist extends EditRecord
{
    protected static string $resource = Compressor1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
