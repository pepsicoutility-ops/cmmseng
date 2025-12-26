<?php

namespace App\Filament\Resources\CbmSchedules\Pages;

use App\Filament\Resources\CbmSchedules\CbmScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCbmSchedule extends CreateRecord
{
    protected static string $resource = CbmScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
