<?php

namespace App\Filament\Imports;

use App\Models\AhuChecklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AhuChecklistImporter extends Importer
{
    protected static ?string $model = AhuChecklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('shift')
                ->numeric()
                ->rules(['required', 'integer'])
                ->label('Shift'),
            ImportColumn::make('gpid')
                ->rules(['required', 'string'])
                ->label('GPID'),
            ImportColumn::make('name')
                ->rules(['required', 'string'])
                ->label('Name'),
            ImportColumn::make('ahu_mb_1_1_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.1 HF'),
            ImportColumn::make('ahu_mb_1_1_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.1 PF'),
            ImportColumn::make('ahu_mb_1_1_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.1 MF'),
            ImportColumn::make('ahu_mb_1_2_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.2 HF'),
            ImportColumn::make('ahu_mb_1_2_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.2 MF'),
            ImportColumn::make('ahu_mb_1_2_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.2 PF'),
            ImportColumn::make('ahu_mb_1_3_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.3 HF'),
            ImportColumn::make('ahu_mb_1_3_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.3 MF'),
            ImportColumn::make('ahu_mb_1_3_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU MB 1.3 PF'),
            ImportColumn::make('pau_mb_1_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB 1 PF'),
            ImportColumn::make('pau_mb_pr_1a_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1A HF'),
            ImportColumn::make('pau_mb_pr_1a_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1A MF'),
            ImportColumn::make('pau_mb_pr_1a_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1A PF'),
            ImportColumn::make('pau_mb_pr_1b_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1B HF'),
            ImportColumn::make('pau_mb_pr_1b_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1B MF'),
            ImportColumn::make('pau_mb_pr_1b_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1B PF'),
            ImportColumn::make('pau_mb_pr_1c_hf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1C HF'),
            ImportColumn::make('pau_mb_pr_1c_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1C PF'),
            ImportColumn::make('pau_mb_pr_1c_mf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('PAU MB PR 1C MF'),
            ImportColumn::make('ahu_vrf_mb_ms_1a_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB MS 1A PF'),
            ImportColumn::make('ahu_vrf_mb_ms_1b_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB MS 1B PF'),
            ImportColumn::make('ahu_vrf_mb_ms_1c_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB MS 1C PF'),
            ImportColumn::make('ahu_vrf_mb_ss_1a_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB SS 1A PF'),
            ImportColumn::make('ahu_vrf_mb_ss_1b_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB SS 1B PF'),
            ImportColumn::make('ahu_vrf_mb_ss_1c_pf')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->label('AHU VRF MB SS 1C PF'),
            ImportColumn::make('if_pre_filter_a')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter A'),
            ImportColumn::make('if_medium_a')
                ->rules(['nullable', 'string'])
                ->label('IF Medium A'),
            ImportColumn::make('if_hepa_a')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA A'),
            ImportColumn::make('if_pre_filter_b')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter B'),
            ImportColumn::make('if_medium_b')
                ->rules(['nullable', 'string'])
                ->label('IF Medium B'),
            ImportColumn::make('if_hepa_b')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA B'),
            ImportColumn::make('if_pre_filter_c')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter C'),
            ImportColumn::make('if_medium_c')
                ->rules(['nullable', 'string'])
                ->label('IF Medium C'),
            ImportColumn::make('if_hepa_c')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA C'),
            ImportColumn::make('if_pre_filter_d')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter D'),
            ImportColumn::make('if_medium_d')
                ->rules(['nullable', 'string'])
                ->label('IF Medium D'),
            ImportColumn::make('if_hepa_d')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA D'),
            ImportColumn::make('if_pre_filter_e')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter E'),
            ImportColumn::make('if_medium_e')
                ->rules(['nullable', 'string'])
                ->label('IF Medium E'),
            ImportColumn::make('if_hepa_e')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA E'),
            ImportColumn::make('if_pre_filter_f')
                ->rules(['nullable', 'string'])
                ->label('IF Pre Filter F'),
            ImportColumn::make('if_medium_f')
                ->rules(['nullable', 'string'])
                ->label('IF Medium F'),
            ImportColumn::make('if_hepa_f')
                ->rules(['nullable', 'string'])
                ->label('IF HEPA F'),
            ImportColumn::make('notes')
                ->rules(['nullable', 'string'])
                ->label('Notes'),
        ];
    }

    public function resolveRecord(): ?AhuChecklist
    {
        return new AhuChecklist();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your AHU checklist import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        return $body;
    }
}
