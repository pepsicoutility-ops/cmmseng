<?php

namespace App\Filament\Resources\PmExecutions\Pages;

use App\Filament\Resources\PmExecutions\PmExecutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPmExecution extends EditRecord
{
    protected static string $resource = PmExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('complete')
                ->label('Complete PM')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Complete PM Execution')
                ->modalDescription('Are you sure you want to complete this PM execution? This will set the completion time and calculate compliance.')
                ->action(function () {
                    $execution = $this->record;
                    
                    $execution->update([
                        'actual_end' => now(),
                        'status' => 'completed',
                    ]);
                    
                    // Calculate duration
                    $duration = $execution->actual_start->diffInMinutes($execution->actual_end);
                    $execution->update(['duration' => $duration]);
                    
                    // Calculate compliance
                    $isOnTime = $execution->actual_end <= $execution->scheduled_date->addDay();
                    $execution->update([
                        'compliance_status' => $isOnTime ? 'on_time' : 'late',
                        'is_on_time' => $isOnTime
                    ]);
                    
                    // Deduct inventory if parts were used
                    if ($execution->partsUsage()->count() > 0) {
                        $inventoryService = app(\App\Services\InventoryService::class);
                        $inventoryService->deductPartsFromPmExecution($execution);
                    }
                    
                    // Calculate PM cost
                    $pmService = app(\App\Services\PmService::class);
                    $pmService->calculateCost($execution);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('PM Execution Completed')
                        ->body('Parts inventory deducted and cost calculated.')
                        ->success()
                        ->send();
                    
                    return redirect(\App\Filament\Resources\PmExecutions\PmExecutionResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'in_progress'),
            DeleteAction::make()
                ->visible(fn () => in_array(\Illuminate\Support\Facades\Auth::user()->role, ['super_admin', 'manager'])),
        ];
    }
    
    protected function afterSave(): void
    {
        $execution = $this->record;
        
        // Calculate duration if actual_end is provided
        if ($execution->actual_end) {
            $duration = $execution->actual_start->diffInMinutes($execution->actual_end);
            $execution->update(['duration' => $duration]);
            
            // Update status to completed if not already
            if ($execution->status !== 'completed') {
                $execution->update(['status' => 'completed']);
            }
            
            // Calculate compliance
            $isOnTime = $execution->actual_end <= $execution->scheduled_date->addDay();
            $execution->update([
                'compliance_status' => $isOnTime ? 'on_time' : 'late',
                'is_on_time' => $isOnTime
            ]);
            
            // Calculate PM cost
            $pmService = app(\App\Services\PmService::class);
            $pmService->calculateCost($execution);
        }
    }
}
