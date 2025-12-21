<?php

namespace App\Filament\Resources\EquipmentTroubles\Pages;

use App\Filament\Resources\EquipmentTroubles\EquipmentTroubleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentTrouble extends EditRecord
{
    protected static string $resource = EquipmentTroubleResource::class;

    protected function afterSave(): void
    {
        // Sync technicians relationship dari form data
        if (isset($this->data['technicians'])) {
            $this->record->technicians()->sync($this->data['technicians']);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
