<?php

namespace App\Filament\Resources\PmExecutions\Pages;

use App\Filament\Resources\PmExecutions\PmExecutionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPmExecution extends ViewRecord
{
    protected static string $resource = PmExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
