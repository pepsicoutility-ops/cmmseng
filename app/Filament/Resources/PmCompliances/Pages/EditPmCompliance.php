<?php

namespace App\Filament\Resources\PmCompliances\Pages;

use App\Filament\Resources\PmCompliances\PmComplianceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPmCompliance extends EditRecord
{
    protected static string $resource = PmComplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
