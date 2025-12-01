<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(UserImporter::class)
                ->label('Import Users')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Employee Data')
                ->modalDescription('Upload an Excel file containing employee data. Download the template below for the correct format.')
                ->csvDelimiter(',')
                ->maxRows(1000)
                ->chunkSize(100),
            CreateAction::make(),
        ];
    }
}
