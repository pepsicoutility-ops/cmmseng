<?php

namespace App\Filament\Exports;

use App\Models\Chiller1Checklist;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class Chiller1ChecklistExporter extends Exporter
{
    protected static ?string $model = Chiller1Checklist::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('shift')
                ->label('Shift'),
            ExportColumn::make('gpid')
                ->label('GPID'),
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('sat_evap_t')
                ->label('Sat Evap T'),
            ExportColumn::make('sat_dis_t')
                ->label('Sat Dis T'),
            ExportColumn::make('dis_superheat')
                ->label('Dis Superheat'),
            ExportColumn::make('lcl')
                ->label('LCL'),
            ExportColumn::make('fla')
                ->label('FLA'),
            ExportColumn::make('ecl')
                ->label('ECL'),
            ExportColumn::make('lel')
                ->label('LEL'),
            ExportColumn::make('eel')
                ->label('EEL'),
            ExportColumn::make('evap_p')
                ->label('Evap P'),
            ExportColumn::make('conds_p')
                ->label('Conds P'),
            ExportColumn::make('oil_p')
                ->label('Oil P'),
            ExportColumn::make('evap_t_diff')
                ->label('Evap T Diff'),
            ExportColumn::make('conds_t_diff')
                ->label('Conds T Diff'),
            ExportColumn::make('reff_levels')
                ->label('Reff Levels'),
            ExportColumn::make('motor_amps')
                ->label('Motor Amps'),
            ExportColumn::make('motor_volts')
                ->label('Motor Volts'),
            ExportColumn::make('heatsink_t')
                ->label('Heatsink T'),
            ExportColumn::make('run_hours')
                ->label('Run Hours'),
            ExportColumn::make('motor_t')
                ->label('Motor T'),
            ExportColumn::make('comp_oil_level')
                ->label('Comp Oil Level'),
            ExportColumn::make('cooler_reff_small_temp_diff')
                ->label('Cooler Reff Small Temp Diff'),
            ExportColumn::make('cooler_liquid_inlet_pressure')
                ->label('Cooler Liquid Inlet Pressure'),
            ExportColumn::make('cooler_liquid_outlet_pressure')
                ->label('Cooler Liquid Outlet Pressure'),
            ExportColumn::make('cooler_pressure_drop')
                ->label('Cooler Pressure Drop'),
            ExportColumn::make('cond_reff_small_temp_diff')
                ->label('Cond Reff Small Temp Diff'),
            ExportColumn::make('cond_liquid_inlet_pressure')
                ->label('Cond Liquid Inlet Pressure'),
            ExportColumn::make('cond_liquid_outlet_pressure')
                ->label('Cond Liquid Outlet Pressure'),
            ExportColumn::make('cond_pressure_drop')
                ->label('Cond Pressure Drop'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your chiller 1 checklist export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
