<?php

namespace App\Filament\Resources\KaizenResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\KaizenResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListKaizens extends ListRecords
{
    protected static string $resource = KaizenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Kaizen Management';
    }
}
