<?php

namespace App\Filament\Resources\PmCompliances\Pages;

use App\Filament\Resources\PmCompliances\PmComplianceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPmCompliances extends ListRecords
{
    protected static string $resource = PmComplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
