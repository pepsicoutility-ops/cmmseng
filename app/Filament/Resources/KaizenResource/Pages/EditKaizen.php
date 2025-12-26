<?php

namespace App\Filament\Resources\KaizenResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\KaizenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKaizen extends EditRecord
{
    protected static string $resource = KaizenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
