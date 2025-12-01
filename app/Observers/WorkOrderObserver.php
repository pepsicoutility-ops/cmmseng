<?php

namespace App\Observers;

use App\Models\WorkOrder;
use App\Services\TelegramService;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WorkOrderObserver
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle the WorkOrder "created" event.
     */
    public function created(WorkOrder $workOrder): void
    {
        $operatorName = User::find($workOrder->gpid)?->name ?? 'Unknown';
        $equipment = $workOrder->asset?->name ?? 'N/A';

        // Send Telegram notification for new WO (optional, skip if not configured)
        try {
            $this->telegram->sendWoNotification([
                'wo_number' => $workOrder->wo_number,
                'operator_name' => $operatorName,
                'equipment' => $equipment,
                'problem_type' => $workOrder->problem_type,
                'status' => $workOrder->status,
                'assign_to' => $workOrder->assign_to
            ]);
        } catch (\Exception $e) {
            // Telegram not configured, skip notification
            Log::info('Telegram notification skipped: ' . $e->getMessage());
        }

        // Notify assigned department technicians
        if ($workOrder->assign_to) {
            $technicians = User::where('role', 'technician')
                ->where('department', $workOrder->assign_to)
                ->get();

            foreach ($technicians as $technician) {
                Notification::make()
                    ->title('New Work Order Assigned')
                    ->body("WO {$workOrder->wo_number} has been assigned to your department. Problem: {$workOrder->problem_type}")
                    ->info()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->sendToDatabase($technician);
            }
        }
    }

    /**
     * Handle the WorkOrder "updated" event.
     */
    public function updated(WorkOrder $workOrder): void
    {
        // Only notify on status change
        if ($workOrder->isDirty('status')) {
            $operatorName = User::find($workOrder->gpid)?->name ?? 'Unknown';
            $equipment = $workOrder->asset?->name ?? 'N/A';

            // Send Telegram notification
            $this->telegram->sendWoNotification([
                'wo_number' => $workOrder->wo_number,
                'operator_name' => $operatorName,
                'equipment' => $equipment,
                'problem_type' => $workOrder->problem_type,
                'status' => $workOrder->status,
                'assign_to' => $workOrder->assign_to
            ]);

            // Notify operator of status change
            $operator = User::find($workOrder->gpid);
            if ($operator) {
                Notification::make()
                    ->title('Work Order Status Updated')
                    ->body("Your WO {$workOrder->wo_number} status changed to: " . strtoupper($workOrder->status))
                    ->success()
                    ->icon('heroicon-o-check-circle')
                    ->sendToDatabase($operator);
            }
        }
    }
}
