<?php

namespace App\Observers;

use Exception;
use App\Models\WorkOrder;
use App\Services\TelegramService;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
        // Clear dashboard cache
        $this->clearDashboardCache($workOrder);
        
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
        } catch (Exception $e) {
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
        // Clear dashboard cache on status change
        if ($workOrder->isDirty('status')) {
            $this->clearDashboardCache($workOrder);
        }
        
        // Auto-flag RCA requirement when downtime is updated
        if ($workOrder->isDirty('total_downtime')) {
            $this->checkAndFlagRcaRequirement($workOrder);
        }

        // Only notify on status change
        if ($workOrder->isDirty('status')) {
            $operatorName = User::find($workOrder->gpid)?->name ?? 'Unknown';
            $equipment = $workOrder->asset?->name ?? 'N/A';

            // Send Telegram notification
            try {
                $this->telegram->sendWoNotification([
                    'wo_number' => $workOrder->wo_number,
                    'operator_name' => $operatorName,
                    'equipment' => $equipment,
                    'problem_type' => $workOrder->problem_type,
                    'status' => $workOrder->status,
                    'assign_to' => $workOrder->assign_to
                ]);
            } catch (Exception $e) {
                Log::info('Telegram notification skipped: ' . $e->getMessage());
            }

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

            // Notify if RCA is required when WO is completed
            if ($workOrder->status === 'completed' && $workOrder->rca_required && $workOrder->rca_status === 'pending') {
                $this->notifyRcaRequired($workOrder);
            }
        }
    }

    /**
     * Check and flag RCA requirement based on downtime threshold
     * Threshold: >10 minutes downtime requires RCA
     */
    protected function checkAndFlagRcaRequirement(WorkOrder $workOrder): void
    {
        $downtimeThreshold = 10; // minutes
        
        if (($workOrder->total_downtime ?? 0) > $downtimeThreshold && !$workOrder->rca_required) {
            // Use update without triggering observer again
            WorkOrder::withoutEvents(function () use ($workOrder) {
                $workOrder->update([
                    'rca_required' => true,
                    'rca_status' => 'pending',
                ]);
            });

            Log::info("RCA flagged as required for WO {$workOrder->wo_number} - Downtime: {$workOrder->total_downtime} min");
        }
    }

    /**
     * Notify relevant users that RCA is required
     */
    protected function notifyRcaRequired(WorkOrder $workOrder): void
    {
        // Notify technicians in the department
        $technicians = User::where('role', 'technician')
            ->where('department', $workOrder->assign_to)
            ->where('is_active', true)
            ->get();

        foreach ($technicians as $technician) {
            Notification::make()
                ->title('RCA Required')
                ->body("WO {$workOrder->wo_number} requires Root Cause Analysis (Downtime: {$workOrder->total_downtime} min). Please create RCA.")
                ->warning()
                ->icon('heroicon-o-magnifying-glass-circle')
                ->sendToDatabase($technician);
        }

        // Notify Asisten Manager
        $asistenManagers = User::where('role', 'asisten_manager')
            ->where('department', $workOrder->assign_to)
            ->where('is_active', true)
            ->get();

        foreach ($asistenManagers as $am) {
            Notification::make()
                ->title('RCA Pending for WO')
                ->body("WO {$workOrder->wo_number} requires RCA. Downtime: {$workOrder->total_downtime} minutes.")
                ->warning()
                ->icon('heroicon-o-magnifying-glass-circle')
                ->sendToDatabase($am);
        }
    }

    /**
     * Clear dashboard widget cache when WO changes
     */
    protected function clearDashboardCache(WorkOrder $workOrder): void
    {
        Cache::forget('dashboard.overview_stats');
        Cache::forget("dashboard.wo_status.{$workOrder->assign_to}");
        Cache::forget('dashboard.wo_status.all');
    }
}
