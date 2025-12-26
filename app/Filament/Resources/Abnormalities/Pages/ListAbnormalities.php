<?php

namespace App\Filament\Resources\Abnormalities\Pages;

use App\Filament\Resources\Abnormalities\AbnormalityResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAbnormalities extends ListRecords
{
    protected static string $resource = AbnormalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Report Abnormality'),
        ];
    }
}
