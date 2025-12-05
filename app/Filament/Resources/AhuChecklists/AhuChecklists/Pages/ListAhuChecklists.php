<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists\Pages;

use App\Filament\Resources\AhuChecklists\AhuChecklists\AhuChecklistResource;
use App\Models\AhuChecklist;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\AhuChecklistExporter;
use App\Filament\Imports\AhuChecklistImporter;
use Illuminate\Support\Facades\Response;

class ListAhuChecklists extends ListRecords
{
    protected static string $resource = AhuChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(AhuChecklistImporter::class),
            Action::make('downloadCsv')
                ->label('Download CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $checklists = AhuChecklist::all();
                    $filename = 'ahu-checklists-' . now()->format('Y-m-d-His') . '.csv';
                    
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    
                    $callback = function() use ($checklists) {
                        $file = fopen('php://output', 'w');
                        
                        fputcsv($file, [
                            'ID', 'Shift', 'Date', 'PF Before', 'PF After',
                            'MF Before', 'MF After', 'HF Before', 'HF After',
                            'Voltage R', 'Voltage S', 'Voltage T', 'Status', 'Remarks',
                            'Created By', 'Created At', 'Updated At'
                        ]);
                        
                        foreach ($checklists as $c) {
                            fputcsv($file, [
                                $c->id, $c->shift, $c->date, $c->pf_before, $c->pf_after,
                                $c->mf_before, $c->mf_after, $c->hf_before, $c->hf_after,
                                $c->voltage_r, $c->voltage_s, $c->voltage_t,
                                $c->status, $c->remarks, $c->created_by_gpid,
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
