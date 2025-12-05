<?php

namespace App\Filament\Imports;

use App\Models\Chiller2Checklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class Chiller2ChecklistImporter extends Importer
{
    protected static ?string $model = Chiller2Checklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('shift')->numeric()->rules(['required', 'integer']),
            ImportColumn::make('gpid')->rules(['required', 'string', 'max:255']),
            ImportColumn::make('name')->rules(['required', 'string', 'max:255']),
            ImportColumn::make('sat_evap_t')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('sat_dis_t')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('dis_superheat')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('lcl')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('fla')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('ecl')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('lel')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('eel')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('evap_p')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('conds_p')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('oil_p')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('evap_t_diff')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('conds_t_diff')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('reff_levels')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_amps')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_volts')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('heatsink_t')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('run_hours')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('motor_t')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('comp_oil_level')->rules(['nullable', 'string']),
            ImportColumn::make('cooler_reff_small_temp_diff')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_liquid_inlet_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_liquid_outlet_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cooler_pressure_drop')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_reff_small_temp_diff')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_liquid_inlet_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_liquid_outlet_pressure')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('cond_pressure_drop')->numeric()->rules(['nullable', 'numeric']),
            ImportColumn::make('notes')->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Chiller2Checklist
    {
        return new Chiller2Checklist();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your chiller 2 checklist import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }
}
