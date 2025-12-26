<?php

namespace App\Filament\Resources\Abnormalities\Pages;

use App\Filament\Resources\Abnormalities\AbnormalityResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditAbnormality extends EditRecord
{
    protected static string $resource = AbnormalityResource::class;

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
