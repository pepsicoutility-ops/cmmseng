<?php

namespace App\Filament\Resources\RootCauseAnalyses\Pages;

use App\Filament\Resources\RootCauseAnalyses\RootCauseAnalysisResource;
use Filament\Resources\Pages\ListRecords;

class ListRootCauseAnalyses extends ListRecords
{
    protected static string $resource = RootCauseAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
