<?php

namespace App\Filament\Resources\KaizenResource\Pages;

use App\Filament\Resources\KaizenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKaizen extends CreateRecord
{
    protected static string $resource = KaizenResource::class;

    public function getTitle(): string
    {
        return 'Submit New Kaizen';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
