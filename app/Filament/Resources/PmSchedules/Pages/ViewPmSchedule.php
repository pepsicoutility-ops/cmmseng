<?php

namespace App\Filament\Resources\PmSchedules\Pages;

use App\Filament\Resources\PmSchedules\PmScheduleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPmSchedule extends ViewRecord
{
    protected static string $resource = PmScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
