<?php

namespace App\Services;

use App\Models\PmExecution;
use App\Models\PmCost;

/**
 * PM Service
 * 
 * Business logic service for Preventive Maintenance operations.
 * Handles PM cost calculations, execution completion, and compliance tracking.
 * 
 * Cost Calculation Formula:
 * - Labor Cost = (duration_minutes / 60) × hourly_rate
 * - Parts Cost = SUM(parts_usages.cost)
 * - Overhead Cost = (labor_cost + parts_cost) × 0.1
 * - Total Cost = labor_cost + parts_cost + overhead_cost
 * 
 * @package App\Services
 */
class PmService
{
    /**
     * Calculate and store PM execution cost
     * 
     * Calculates labor cost based on duration and hourly rate,
     * sums parts cost from parts usage records, adds 10% overhead,
     * and stores the breakdown in pm_costs table.
     * 
     * @param PmExecution $execution PM execution instance with duration and parts usage
     * @return void
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If execution not found
     */
    public function calculateCost(PmExecution $execution): void
    {
        // Labour cost based on duration and technician hourly rate
        $duration = $execution->duration ?? 0; // in minutes
        $hourlyRate = config('cmms.labour_hourly_rate', 50000); // IDR per hour
        $labourCost = ($duration / 60) * $hourlyRate;
        
        // Parts cost from parts usage
        $partsCost = $execution->partsUsage()->sum('cost') ?? 0;
        
        // Overhead cost (10% of labour + parts)
        $overheadCost = ($labourCost + $partsCost) * 0.1;
        
        // Total cost
        $totalCost = $labourCost + $partsCost + $overheadCost;
        
        // Create or update PM cost record
        PmCost::updateOrCreate(
            ['pm_execution_id' => $execution->id],
            [
                'labour_cost' => round($labourCost, 2),
                'parts_cost' => round($partsCost, 2),
                'overhead_cost' => round($overheadCost, 2),
                'total_cost' => round($totalCost, 2)
            ]
        );
    }
    
    /**
     * Complete PM execution with automatic cost calculation
     * 
     * Calculates duration if not set (from actual_start to actual_end),
     * saves the PM execution record, and triggers cost calculation.
     * 
     * @param PmExecution $execution PM execution instance to complete
     * @param array<string, mixed> $data Additional data (currently unused)
     * @return void
     * 
     * @see calculateCost() For cost calculation details
     */
    public function completePmExecution(PmExecution $execution, array $data): void
    {
        // Calculate duration if not set
        if (!$execution->duration && $execution->actual_start && $execution->actual_end) {
            $duration = $execution->actual_start->diffInMinutes($execution->actual_end);
            $execution->duration = $duration;
        }
        
        // Save PM execution
        $execution->save();
        
        // Calculate and store cost
        $this->calculateCost($execution);
    }
    
    /**
     * Recalculate cost for existing PM execution
     * 
     * Useful when parts usage is updated after initial completion,
     * or when hourly rates change and historical costs need updating.
     * 
     * @param PmExecution $execution PM execution instance to recalculate
     * @return void
     * 
     * @see calculateCost() Wrapper method that delegates to cost calculation
     */
    public function recalculateCost(PmExecution $execution): void
    {
        $this->calculateCost($execution);
    }
}
