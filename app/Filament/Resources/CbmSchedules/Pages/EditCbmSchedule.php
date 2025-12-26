<?php

namespace App\Filament\Resources\CbmSchedules\Pages;

use App\Filament\Resources\CbmSchedules\CbmScheduleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditCbmSchedule extends EditRecord
{
    protected static string $resource = CbmScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
