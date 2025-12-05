<?php

namespace App\Filament\Resources\Compressor1Checklists\Pages;

use App\Filament\Resources\Compressor1Checklists\Compressor1ChecklistResource;
use App\Models\Compressor1Checklist;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\Compressor1ChecklistExporter;
use App\Filament\Imports\Compressor1ChecklistImporter;
use Illuminate\Support\Facades\Response;

class ListCompressor1Checklists extends ListRecords
{
    protected static string $resource = Compressor1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(Compressor1ChecklistImporter::class),
            Action::make('downloadCsv')
                ->label('Download CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $checklists = Compressor1Checklist::all();
                    $filename = 'compressor1-checklists-' . now()->format('Y-m-d-His') . '.csv';
                    
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    
                    $callback = function() use ($checklists) {
                        $file = fopen('php://output', 'w');
                        
                        // Headers
                        fputcsv($file, [
                            'ID', 'Shift', 'Date', 'Total Run Hours', 'Bearing Oil Temperature',
                            'Bearing Oil Pressure', 'Discharge Pressure', 'Discharge Temperature',
                            'CWS', 'CWR', 'Oil Pressure', 'Oil Temperature', 'Suction Pressure',
                            'Loading', 'LCL', 'FLA', 'Voltage', 'Status', 'Remarks',
                            'Created By', 'Created At', 'Updated At'
                        ]);
                        
                        // Data
                        foreach ($checklists as $checklist) {
                            fputcsv($file, [
                                $checklist->id,
                                $checklist->shift,
                                $checklist->date,
                                $checklist->tot_run_hours,
                                $checklist->bearing_oil_temperature,
                                $checklist->bearing_oil_pressure,
                                $checklist->discharge_pressure,
                                $checklist->discharge_temperature,
                                $checklist->cws,
                                $checklist->cwr,
                                $checklist->oil_pressure,
                                $checklist->oil_temperature,
                                $checklist->suction_pressure,
                                $checklist->loading,
                                $checklist->lcl,
                                $checklist->fla,
                                $checklist->voltage,
                                $checklist->status,
                                $checklist->remarks,
                                $checklist->created_by_gpid,
                                $checklist->created_at,
                                $checklist->updated_at,
                            ]);
                        }
                        
                        fclose($file);
                    };
                    
                    return Response::stream($callback, 200, $headers);
                }),
        ];
    }
}
