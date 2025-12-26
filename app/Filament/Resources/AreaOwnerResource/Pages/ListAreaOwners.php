<?php

namespace App\Filament\Resources\AreaOwnerResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AreaOwnerResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAreaOwners extends ListRecords
{
    protected static string $resource = AreaOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Area Ownership Management';
    }
}
