<?php

namespace App\Filament\Resources\AreaOwnerResource\Pages;

use App\Filament\Resources\AreaOwnerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAreaOwner extends CreateRecord
{
    protected static string $resource = AreaOwnerResource::class;

    public function getTitle(): string
    {
        return 'Assign Area Owner';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
