<?php

namespace App\Filament\Resources\InventoryReports\Pages;

use App\Filament\Resources\InventoryReports\InventoryReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryReports extends ManageRecords
{
    protected static string $resource = InventoryReportResource::class;
}
