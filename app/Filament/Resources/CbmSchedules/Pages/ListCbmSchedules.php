<?php

namespace App\Filament\Resources\CbmSchedules\Pages;

use App\Filament\Resources\CbmSchedules\CbmScheduleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListCbmSchedules extends ListRecords
{
    protected static string $resource = CbmScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
