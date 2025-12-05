<?php

namespace App\Filament\Exports;

use App\Models\AhuChecklist;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AhuChecklistExporter extends Exporter
{
    protected static ?string $model = AhuChecklist::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('shift')->label('Shift'),
            ExportColumn::make('gpid')->label('GPID'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('ahu_mb_1_1_hf')->label('AHU MB 1.1 HF'),
            ExportColumn::make('ahu_mb_1_1_pf')->label('AHU MB 1.1 PF'),
            ExportColumn::make('ahu_mb_1_1_mf')->label('AHU MB 1.1 MF'),
            ExportColumn::make('ahu_mb_1_2_hf')->label('AHU MB 1.2 HF'),
            ExportColumn::make('ahu_mb_1_2_mf')->label('AHU MB 1.2 MF'),
            ExportColumn::make('ahu_mb_1_2_pf')->label('AHU MB 1.2 PF'),
            ExportColumn::make('ahu_mb_1_3_hf')->label('AHU MB 1.3 HF'),
            ExportColumn::make('ahu_mb_1_3_mf')->label('AHU MB 1.3 MF'),
            ExportColumn::make('ahu_mb_1_3_pf')->label('AHU MB 1.3 PF'),
            ExportColumn::make('pau_mb_1_pf')->label('PAU MB 1 PF'),
            ExportColumn::make('pau_mb_pr_1a_hf')->label('PAU MB PR 1A HF'),
            ExportColumn::make('pau_mb_pr_1a_mf')->label('PAU MB PR 1A MF'),
            ExportColumn::make('pau_mb_pr_1a_pf')->label('PAU MB PR 1A PF'),
            ExportColumn::make('pau_mb_pr_1b_hf')->label('PAU MB PR 1B HF'),
            ExportColumn::make('pau_mb_pr_1b_mf')->label('PAU MB PR 1B MF'),
            ExportColumn::make('pau_mb_pr_1b_pf')->label('PAU MB PR 1B PF'),
            ExportColumn::make('pau_mb_pr_1c_hf')->label('PAU MB PR 1C HF'),
            ExportColumn::make('pau_mb_pr_1c_pf')->label('PAU MB PR 1C PF'),
            ExportColumn::make('pau_mb_pr_1c_mf')->label('PAU MB PR 1C MF'),
            ExportColumn::make('ahu_vrf_mb_ms_1a_pf')->label('AHU VRF MB MS 1A PF'),
            ExportColumn::make('ahu_vrf_mb_ms_1b_pf')->label('AHU VRF MB MS 1B PF'),
            ExportColumn::make('ahu_vrf_mb_ms_1c_pf')->label('AHU VRF MB MS 1C PF'),
            ExportColumn::make('ahu_vrf_mb_ss_1a_pf')->label('AHU VRF MB SS 1A PF'),
            ExportColumn::make('ahu_vrf_mb_ss_1b_pf')->label('AHU VRF MB SS 1B PF'),
            ExportColumn::make('ahu_vrf_mb_ss_1c_pf')->label('AHU VRF MB SS 1C PF'),
            ExportColumn::make('if_pre_filter_a')->label('IF Pre Filter A'),
            ExportColumn::make('if_medium_a')->label('IF Medium A'),
            ExportColumn::make('if_hepa_a')->label('IF HEPA A'),
            ExportColumn::make('if_pre_filter_b')->label('IF Pre Filter B'),
            ExportColumn::make('if_medium_b')->label('IF Medium B'),
            ExportColumn::make('if_hepa_b')->label('IF HEPA B'),
            ExportColumn::make('if_pre_filter_c')->label('IF Pre Filter C'),
            ExportColumn::make('if_medium_c')->label('IF Medium C'),
            ExportColumn::make('if_hepa_c')->label('IF HEPA C'),
            ExportColumn::make('if_pre_filter_d')->label('IF Pre Filter D'),
            ExportColumn::make('if_medium_d')->label('IF Medium D'),
            ExportColumn::make('if_hepa_d')->label('IF HEPA D'),
            ExportColumn::make('if_pre_filter_e')->label('IF Pre Filter E'),
            ExportColumn::make('if_medium_e')->label('IF Medium E'),
            ExportColumn::make('if_hepa_e')->label('IF HEPA E'),
            ExportColumn::make('if_pre_filter_f')->label('IF Pre Filter F'),
            ExportColumn::make('if_medium_f')->label('IF Medium F'),
            ExportColumn::make('if_hepa_f')->label('IF HEPA F'),
            ExportColumn::make('notes')->label('Notes'),
            ExportColumn::make('created_at')->label('Created At'),
            ExportColumn::make('updated_at')->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your AHU checklist export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        return $body;
    }
}
