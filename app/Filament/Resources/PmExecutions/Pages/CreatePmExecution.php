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
        
        // Set actual_start if not provided
        if (!isset($data['actual_start'])) {
            $data['actual_start'] = now();
        }
        
        // Ensure scheduled_date has a value
        if (!isset($data['scheduled_date']) || !$data['scheduled_date']) {
            // Try to get from PM Schedule
            if (isset($data['pm_schedule_id'])) {
                $pmSchedule = \App\Models\PmSchedule::find($data['pm_schedule_id']);
                if ($pmSchedule && $pmSchedule->next_due_date) {
                    $data['scheduled_date'] = $pmSchedule->next_due_date;
                } else {
                    $data['scheduled_date'] = now();
                }
            } else {
                $data['scheduled_date'] = now();
            }
        }
        
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
