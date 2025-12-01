<?php

namespace App\Services;

use App\Models\PmSchedule;
use App\Models\PmExecution;
use App\Models\PmCompliance;
use Carbon\Carbon;

class ComplianceService
{
    /**
     * Update PM compliance for a given period
     * 
     * @param string $period 'week' or 'month'
     * @return void
     */
    public function updatePmCompliance(string $period = 'week'): void
    {
        $startDate = $period === 'week' 
            ? now()->startOfWeek()
            : now()->startOfMonth();
        $endDate = $period === 'week'
            ? now()->endOfWeek()
            : now()->endOfMonth();
        
        // Total PM executions scheduled in this period
        $totalPm = PmExecution::whereBetween('scheduled_date', [$startDate, $endDate])->count();
        
        // Completed PM in this period (actual_end within period)
        $completedPm = PmExecution::where('status', 'completed')
            ->whereBetween('actual_end', [$startDate, $endDate])
            ->count();
        
        // Overdue PM = completed late (is_on_time = false) + not yet completed past scheduled date
        $overduePm = PmExecution::where(function ($query) use ($startDate, $endDate) {
                // Completed late (completed in this period but marked as late)
                $query->where('status', 'completed')
                    ->whereBetween('actual_end', [$startDate, $endDate])
                    ->where('is_on_time', false);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                // Not yet completed and past scheduled date + 1 day grace period
                $query->where('status', '!=', 'completed')
                    ->where('scheduled_date', '<', now()->subDay())
                    ->whereBetween('scheduled_date', [$startDate, $endDate]);
            })
            ->count();
        
        // On-time PM = completed on time (is_on_time = true)
        $onTimePm = PmExecution::where('status', 'completed')
            ->whereBetween('actual_end', [$startDate, $endDate])
            ->where('is_on_time', true)
            ->count();
        
        // Calculate compliance percentage (on-time / total * 100)
        $compliancePercentage = $totalPm > 0 
            ? ($onTimePm / $totalPm) * 100
            : 100; // If no PM scheduled, 100% compliance
        
        // Ensure compliance is between 0-100
        $compliancePercentage = max(0, min(100, $compliancePercentage));
        
        // Create or update compliance record
        PmCompliance::updateOrCreate(
            [
                'period' => $period,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString()
            ],
            [
                'total_pm' => $totalPm,
                'completed_pm' => $completedPm,
                'overdue_pm' => $overduePm,
                'compliance_percentage' => round($compliancePercentage, 2)
            ]
        );
    }
    
    /**
     * Get compliance status color based on percentage
     * 
     * @param float $percentage
     * @return string
     */
    public function getComplianceColor(float $percentage): string
    {
        $excellentThreshold = config('cmms.compliance_excellent_threshold', 95);
        $goodThreshold = config('cmms.compliance_good_threshold', 85);
        
        if ($percentage >= $excellentThreshold) {
            return 'success'; // Green
        } elseif ($percentage >= $goodThreshold) {
            return 'warning'; // Yellow
        } else {
            return 'danger'; // Red
        }
    }
}
