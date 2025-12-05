<?php

namespace App\Filament\Resources\Compressor2Checklists\Pages;

use App\Filament\Resources\Compressor2Checklists\Compressor2ChecklistResource;
use App\Models\Compressor2Checklist;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\Compressor2ChecklistExporter;
use App\Filament\Imports\Compressor2ChecklistImporter;
use Illuminate\Support\Facades\Response;

class ListCompressor2Checklists extends ListRecords
{
    protected static string $resource = Compressor2ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(Compressor2ChecklistImporter::class),
            Action::make('downloadCsv')
                ->label('Download CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $checklists = Compressor2Checklist::all();
                    $filename = 'compressor2-checklists-' . now()->format('Y-m-d-His') . '.csv';
                    
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    
                    $callback = function() use ($checklists) {
                        $file = fopen('php://output', 'w');
                        
                        fputcsv($file, [
                            'ID', 'Shift', 'Date', 'Total Run Hours', 'Bearing Oil Temperature',
                            'Bearing Oil Pressure', 'Discharge Pressure', 'Discharge Temperature',
                            'CWS', 'CWR', 'Oil Pressure', 'Oil Temperature', 'Suction Pressure',
                            'Loading', 'LCL', 'FLA', 'Voltage', 'Status', 'Remarks',
                            'Created By', 'Created At', 'Updated At'
                        ]);
                        
                        foreach ($checklists as $c) {
                            fputcsv($file, [
                                $c->id, $c->shift, $c->date, $c->tot_run_hours,
                                $c->bearing_oil_temperature, $c->bearing_oil_pressure,
                                $c->discharge_pressure, $c->discharge_temperature,
                                $c->cws, $c->cwr, $c->oil_pressure, $c->oil_temperature,
                                $c->suction_pressure, $c->loading, $c->lcl, $c->fla,
                                $c->voltage, $c->status, $c->remarks, $c->created_by_gpid,
                                $c->created_at, $c->updated_at
                            ]);
                        }
                        
                        fclose($file);
                    };
                    
                    return Response::stream($callback, 200, $headers);
                }),
        ];
    }
}
