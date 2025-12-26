<?php

namespace App\Filament\Resources\PmSchedules\Pages;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\PmSchedules\PmScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPmSchedules extends ListRecords
{
    protected static string $resource = PmScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => 
                    in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])
                ),
        ];
    }
}
