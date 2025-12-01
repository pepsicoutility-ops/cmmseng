<?php

namespace App\Filament\Resources\WoReports\Pages;

use App\Filament\Resources\WoReports\WoReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWoReports extends ManageRecords
{
    protected static string $resource = WoReportResource::class;

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->with(['cost', 'asset', 'partsUsage']);
    }

}
