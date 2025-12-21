<?php

namespace App\Filament\Resources\EquipmentTroubles\Pages;

use App\Filament\Resources\EquipmentTroubles\EquipmentTroubleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewEquipmentTrouble extends ViewRecord
{
    protected static string $resource = EquipmentTroubleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])),
        ];
    }
}
