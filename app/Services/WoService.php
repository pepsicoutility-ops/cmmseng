<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WoCost;
use App\Models\WoProcess;

/**
 * Work Order Service
 * 
 * Business logic service for Work Order operations.
 * Handles WO completion, MTTR calculation, downtime tracking, and cost calculations.
 * 
 * MTTR Calculation:
 * - MTTR (Mean Time To Repair) = time from started_at to completed_at in minutes
 * - Downtime = total equipment downtime in minutes
 * 
 * Cost Calculation Formula:
 * - Labor Cost = (mttr_minutes / 60) × hourly_rate
 * - Parts Cost = SUM(wo_parts_usages.cost)
 * - Downtime Cost = (downtime_minutes / 60) × downtime_rate_per_hour
 * - Total Cost = labor_cost + parts_cost + downtime_cost
 * 
 * @package App\Services
 */
class WoService
{
    /**
     * Complete work order with parts usage, calculations, and inventory deduction
     * 
     * This method performs the following steps:
     * 1. Calculate downtime and MTTR from process history
     * 2. Save parts usage records if provided
     * 3. Deduct parts from inventory (two-way sync)
     * 4. Calculate all costs (labor, parts, downtime)
     * 5. Update work order status to 'completed'
     * 
     * @param WorkOrder $wo Work order instance to complete
     * @param array<string, mixed> $data Additional data including:
     *        - parts_usage: array Array of parts used [['part_id' => int, 'quantity' => int], ...]
     * @return void
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If part not found
     * 
     * @see calculateDowntime() For downtime calculation logic
     * @see calculateWoCost() For cost calculation logic
     * @see InventoryService::deductPartsFromWorkOrder() For inventory deduction
     */
    public function completeWorkOrder(WorkOrder $wo, array $data): void
    {
        // 1. Calculate downtime and MTTR from process history
        $processes = $wo->processes()->orderBy('timestamp')->get();
        $downtime = $this->calculateDowntime($processes);
        
        // 2. MTTR uses the same calculation as downtime
        // Both measure time from start to complete
        $mttr = $downtime;
        
        // 3. Save parts usage (if provided)
        if (!empty($data['parts_usage'])) {
            foreach ($data['parts_usage'] as $partUsage) {
                // Get part to calculate cost
                $part = \App\Models\Part::find($partUsage['part_id']);
                $quantity = $partUsage['quantity'];
                $cost = $part ? ($part->unit_price * $quantity) : 0;
                
                \App\Models\WoPartsUsage::create([
                    'work_order_id' => $wo->id,
                    'part_id' => $partUsage['part_id'],
                    'quantity' => $quantity,
                    'cost' => $cost,
                ]);
            }
            
            // 4. Deduct inventory
            app(InventoryService::class)->deductPartsFromWorkOrder($wo);
        }
        
        // 5. Calculate costs
        $this->calculateWoCost($wo, $downtime, $mttr);
        
        // 6. Update WO
        $wo->update([
            'status' => 'completed',
            'completed_at' => now(),
            'total_downtime' => $downtime,
            'mttr' => $mttr
        ]);
    }
    
    /**
     * Calculate total downtime from work order processes
     * 
     * Downtime is measured from the earliest 'start' timestamp
     * to the latest 'complete' timestamp in the wo_processes table.
     * Rounded up to nearest minute.
     * 
     * @param \Illuminate\Database\Eloquent\Collection<int, WoProcess> $processes WO process records ordered by timestamp
     * @return int Total downtime in minutes (rounded up)
     * 
     * @example
     * // Process 1: start at 08:00, Process 2: complete at 10:30
     * // Downtime = 150 minutes (2.5 hours)
     */
    private function calculateDowntime($processes): int
    {
        $startTime = null;
        $completeTime = null;
        
        foreach ($processes as $process) {
            if ($process->action === 'start' && !$startTime) {
                $startTime = $process->timestamp;
            }
            if ($process->action === 'complete') {
                $completeTime = $process->timestamp;
            }
        }
        
        // If we have both start and complete, calculate downtime
        if ($startTime && $completeTime) {
            $downtime = $startTime->diffInMinutes($completeTime);
            return (int) ceil($downtime);
        }
        
        return 0;
    }
    
    /**
     * Calculate work order cost
     */
    public function calculateWoCost(WorkOrder $wo, int $downtime, int $mttr): void
    {
        // Labour cost based on MTTR
        $hourlyRate = config('cmms.labour_hourly_rate', 50000);
        $labourCost = ($mttr / 60) * $hourlyRate;
        
        // Parts cost from parts usage
        $partsCost = $wo->partsUsage()->sum('cost') ?? 0;
        
        // Downtime cost (optional - based on equipment downtime)
        $downtimeCostPerHour = config('cmms.downtime_cost_per_hour', 100000);
        $downtimeCost = ($downtime / 60) * $downtimeCostPerHour;
        
        // Total cost
        $totalCost = $labourCost + $partsCost + $downtimeCost;
        
        // Create or update WO cost record
        WoCost::updateOrCreate(
            ['work_order_id' => $wo->id],
            [
                'labour_cost' => $labourCost,
                'parts_cost' => $partsCost,
                'downtime_cost' => $downtimeCost,
                'total_cost' => $totalCost
            ]
        );
    }
}
