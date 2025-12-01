<?php

namespace App\Filament\Resources\Compressor2Checklists\Pages;

use App\Filament\Resources\Compressor2Checklists\Compressor2ChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompressor2Checklists extends ListRecords
{
    protected static string $resource = Compressor2ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
