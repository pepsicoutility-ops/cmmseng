<?php

namespace App\Filament\Imports;

use App\Models\Compressor2Checklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class Compressor2ChecklistImporter extends Importer
{
    protected static ?string $model = Compressor2Checklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('shift')->numeric()->rules(['required', 'integer']),
            ImportColumn::make('gpid')->rules(['required', 'string', 'max:255']),
            ImportColumn::make('name')->rules(['required', 'string', 'max:255']),
            ImportColumn::make('tot_run_hours')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('bearing_oil_temperature')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('bearing_oil_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('discharge_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('discharge_temperature')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cws_temperature')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cwr_temperature')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cws_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cwr_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('refrigerant_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('dew_point')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('notes')->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Compressor2Checklist
    {
        return new Compressor2Checklist();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your compressor 2 checklist import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }
}
