<?php

namespace App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages;

use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Chiller1ChecklistResource;
use App\Models\Chiller1Checklist;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\Chiller1ChecklistExporter;
use App\Filament\Imports\Chiller1ChecklistImporter;
use Illuminate\Support\Facades\Response;

class ListChiller1Checklists extends ListRecords
{
    protected static string $resource = Chiller1ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(Chiller1ChecklistImporter::class),
            Action::make('downloadCsv')
                ->label('Download CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $checklists = Chiller1Checklist::all();
                    $filename = 'chiller1-checklists-' . now()->format('Y-m-d-His') . '.csv';
                    
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    
                    $callback = function() use ($checklists) {
                        $file = fopen('php://output', 'w');
                        
                        fputcsv($file, [
                            'ID', 'Shift', 'Date', 'Suction Temperature', 'Suction Pressure',
                            'Discharge Temperature', 'Discharge Pressure', 'Oil Pressure',
                            'Loading', 'Cooling Delta T', 'LCL', 'FLA', 'Status', 'Remarks',
                            'Created By', 'Created At', 'Updated At'
                        ]);
                        
                        foreach ($checklists as $c) {
                            fputcsv($file, [
                                $c->id, $c->shift, $c->date, $c->suction_temperature,
                                $c->suction_pressure, $c->discharge_temperature,
                                $c->discharge_pressure, $c->oil_pressure, $c->loading,
                                $c->cooling_delta_t, $c->lcl, $c->fla, $c->status,
                                $c->remarks, $c->created_by_gpid, $c->created_at, $c->updated_at
                            ]);
                        }
                        
                        fclose($file);
                    };
                    
                    return Response::stream($callback, 200, $headers);
                }),
        ];
    }
}
