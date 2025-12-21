<?php

namespace App\Filament\Resources\EquipmentTroubles\Pages;

use App\Filament\Resources\EquipmentTroubles\EquipmentTroubleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipmentTrouble extends CreateRecord
{
    protected static string $resource = EquipmentTroubleResource::class;

    protected function afterCreate(): void
    {
        // Sync technicians relationship dari form data
        if (isset($this->data['technicians'])) {
            $this->record->technicians()->sync($this->data['technicians']);
        }
    }
}
