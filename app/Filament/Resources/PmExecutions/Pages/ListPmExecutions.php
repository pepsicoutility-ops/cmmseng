<?php

namespace App\Filament\Resources\PmExecutions\Pages;

use App\Filament\Resources\PmExecutions\PmExecutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPmExecutions extends ListRecords
{
    protected static string $resource = PmExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
