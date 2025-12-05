<?php

namespace App\Filament\Exports;

use App\Models\Compressor2Checklist;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class Compressor2ChecklistExporter extends Exporter
{
    protected static ?string $model = Compressor2Checklist::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('shift')->label('Shift'),
            ExportColumn::make('gpid')->label('GPID'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('tot_run_hours')->label('Total Run Hours'),
            ExportColumn::make('bearing_oil_temperature')->label('Bearing Oil Temperature'),
            ExportColumn::make('bearing_oil_pressure')->label('Bearing Oil Pressure'),
            ExportColumn::make('discharge_pressure')->label('Discharge Pressure'),
            ExportColumn::make('discharge_temperature')->label('Discharge Temperature'),
            ExportColumn::make('cws_temperature')->label('CWS Temperature'),
            ExportColumn::make('cwr_temperature')->label('CWR Temperature'),
            ExportColumn::make('cws_pressure')->label('CWS Pressure'),
            ExportColumn::make('cwr_pressure')->label('CWR Pressure'),
            ExportColumn::make('refrigerant_pressure')->label('Refrigerant Pressure'),
            ExportColumn::make('dew_point')->label('Dew Point'),
            ExportColumn::make('notes')->label('Notes'),
            ExportColumn::make('created_at')->label('Created At'),
            ExportColumn::make('updated_at')->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your compressor 2 checklist export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        return $body;
    }
}
