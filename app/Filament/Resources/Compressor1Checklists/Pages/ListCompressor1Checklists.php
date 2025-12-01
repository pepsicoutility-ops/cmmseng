<?php

namespace App\Filament\Resources\Compressor1Checklists\Pages;

use App\Filament\Resources\Compressor1Checklists\Compressor1ChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompressor1Checklists extends ListRecords
{
    protected static string $resource = Compressor1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
