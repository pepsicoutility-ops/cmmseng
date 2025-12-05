<?php

namespace App\Filament\Imports;

use App\Models\Chiller1Checklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class Chiller1ChecklistImporter extends Importer
{
    protected static ?string $model = Chiller1Checklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('shift')
                ->label('Shift')
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('gpid')
                ->label('GPID')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('name')
                ->label('Name')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('sat_evap_t')
                ->label('Sat Evap T')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('sat_dis_t')
                ->label('Sat Dis T')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('dis_superheat')
                ->label('Dis Superheat')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('lcl')
                ->label('LCL')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('fla')
                ->label('FLA')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('ecl')
                ->label('ECL')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('lel')
                ->label('LEL')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('eel')
                ->label('EEL')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('evap_p')
                ->label('Evap P')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('conds_p')
                ->label('Conds P')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('oil_p')
                ->label('Oil P')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('evap_t_diff')
                ->label('Evap T Diff')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('conds_t_diff')
                ->label('Conds T Diff')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('reff_levels')
                ->label('Reff Levels')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_amps')
                ->label('Motor Amps')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_volts')
                ->label('Motor Volts')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('heatsink_t')
                ->label('Heatsink T')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('run_hours')
                ->label('Run Hours')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_t')
                ->label('Motor T')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('comp_oil_level')
                ->label('Comp Oil Level')
                ->rules(['nullable', 'string']),
            ImportColumn::make('cooler_reff_small_temp_diff')
                ->label('Cooler Reff Small Temp Diff')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_liquid_inlet_pressure')
                ->label('Cooler Liquid Inlet Pressure')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_liquid_outlet_pressure')
                ->label('Cooler Liquid Outlet Pressure')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_pressure_drop')
                ->label('Cooler Pressure Drop')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_reff_small_temp_diff')
                ->label('Cond Reff Small Temp Diff')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_liquid_inlet_pressure')
                ->label('Cond Liquid Inlet Pressure')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_liquid_outlet_pressure')
                ->label('Cond Liquid Outlet Pressure')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_pressure_drop')
                ->label('Cond Pressure Drop')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('notes')
                ->label('Notes')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Chiller1Checklist
    {
        return new Chiller1Checklist();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your chiller 1 checklist import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
