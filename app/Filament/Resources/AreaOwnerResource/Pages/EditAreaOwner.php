<?php

namespace App\Filament\Resources\AreaOwnerResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AreaOwnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAreaOwner extends EditRecord
{
    protected static string $resource = AreaOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
