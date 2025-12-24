<?php

namespace App\Filament\Resources\PmExecutions\Pages;

use App\Filament\Resources\PmExecutions\PmExecutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePmExecution extends CreateRecord
{
    protected static string $resource = PmExecutionResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Prefill data from query parameter
        if ($pmScheduleId = request()->query('pm_schedule_id')) {
            $pmSchedule = \App\Models\PmSchedule::find($pmScheduleId);
            
            if ($pmSchedule) {
                $this->form->fill([
                    'pm_schedule_id' => $pmScheduleId,
                    'scheduled_date' => $pmSchedule->next_due_date ?? now(),
                    'actual_start' => now(),
                ]);
            }
        }
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set executed_by_gpid
        $data['executed_by_gpid'] = \Illuminate\Support\Facades\Auth::user()->gpid;
        
        // Set initial status
        $data['status'] = 'in_progress';
        
        // AUTO: Set scheduled_date to creation time (when PM execution is created)
        $data['scheduled_date'] = now();
        
        // AUTO: Set actual_start to now (when "Start Execution" is confirmed by creating this record)
        $data['actual_start'] = now();
        
        // Ensure actual_end is null (will be set by Complete button)
        $data['actual_end'] = null;
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $execution = $this->record;
        
        // Calculate duration if actual_end is provided
        if ($execution->actual_end) {
            $duration = $execution->actual_start->diffInMinutes($execution->actual_end);
            $execution->update(['duration' => $duration]);
            
            // Update status to completed
            $execution->update(['status' => 'completed']);
            
            // Calculate compliance
            $isOnTime = $execution->actual_end <= $execution->scheduled_date->addDay();
            $execution->update([
                'compliance_status' => $isOnTime ? 'on_time' : 'late',
                'is_on_time' => $isOnTime
            ]);
        }
    }
}
