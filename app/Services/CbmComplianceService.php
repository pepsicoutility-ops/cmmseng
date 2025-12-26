<?php

namespace App\Services;

use App\Models\CbmSchedule;
use App\Models\CbmExecution;
use App\Models\CbmCompliance;
use App\Models\CbmParameterThreshold;
use App\Models\CbmAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CbmComplianceService
{
    /**
     * Generate CBM executions for a schedule
     */
    public function generateExecutions(CbmSchedule $schedule, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Skip if schedule has ended
            if ($schedule->end_date && $currentDate > $schedule->end_date) {
                break;
            }

            // Skip if before schedule start
            if ($currentDate < $schedule->start_date) {
                $currentDate->addDay();
                continue;
            }

            switch ($schedule->frequency) {
                case 'per_shift':
                    for ($shift = 1; $shift <= $schedule->shifts_per_day; $shift++) {
                        $this->createExecution($schedule, $currentDate, $shift);
                        $count++;
                    }
                    break;

                case 'daily':
                    $this->createExecution($schedule, $currentDate, null);
                    $count++;
                    break;

                case 'weekly':
                    if ($currentDate->dayOfWeek === Carbon::MONDAY) {
                        $this->createExecution($schedule, $currentDate, null);
                        $count++;
                    }
                    break;

                case 'monthly':
                    if ($currentDate->day === 1) {
                        $this->createExecution($schedule, $currentDate, null);
                        $count++;
                    }
                    break;
            }

            $currentDate->addDay();
        }

        return $count;
    }

    /**
     * Create a single execution record
     */
    protected function createExecution(CbmSchedule $schedule, Carbon $date, ?int $shift): void
    {
        CbmExecution::firstOrCreate([
            'cbm_schedule_id' => $schedule->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_shift' => $shift ?? 1,
        ]);
    }

    /**
     * Calculate compliance for a period
     */
    public function calculateCompliance(
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $areaId = null,
        string $checklistType = 'all'
    ): CbmCompliance {
        $query = CbmExecution::query()
            ->join('cbm_schedules', 'cbm_executions.cbm_schedule_id', '=', 'cbm_schedules.id')
            ->whereBetween('cbm_executions.scheduled_date', [$periodStart, $periodEnd])
            ->where('cbm_schedules.is_active', true);

        if ($areaId) {
            $query->where('cbm_schedules.area_id', $areaId);
        }

        if ($checklistType !== 'all') {
            $query->where('cbm_schedules.checklist_type', $checklistType);
        }

        $stats = $query->selectRaw('
            COUNT(*) as scheduled_count,
            SUM(CASE WHEN cbm_executions.is_executed = 1 THEN 1 ELSE 0 END) as executed_count,
            SUM(CASE WHEN cbm_executions.is_executed = 1 AND cbm_executions.is_on_time = 1 THEN 1 ELSE 0 END) as on_time_count,
            SUM(CASE WHEN cbm_executions.is_executed = 1 AND cbm_executions.is_on_time = 0 THEN 1 ELSE 0 END) as late_count
        ')->first();

        $scheduled = (int) $stats->scheduled_count;
        $executed = (int) $stats->executed_count;
        $onTime = (int) $stats->on_time_count;
        $late = (int) $stats->late_count;
        $missed = $scheduled - $executed;
        $compliance = $scheduled > 0 ? round(($executed / $scheduled) * 100, 2) : 0;

        return CbmCompliance::updateOrCreate(
            [
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'area_id' => $areaId,
                'checklist_type' => $checklistType,
            ],
            [
                'period_end' => $periodEnd,
                'scheduled_count' => $scheduled,
                'executed_count' => $executed,
                'on_time_count' => $onTime,
                'late_count' => $late,
                'missed_count' => $missed,
                'compliance_percentage' => $compliance,
            ]
        );
    }

    /**
     * Check parameter thresholds and create alerts
     */
    public function checkParameterThresholds(string $checklistType, int $checklistId, array $parameters): array
    {
        $alerts = [];
        $thresholds = CbmParameterThreshold::where('checklist_type', $checklistType)
            ->where('is_active', true)
            ->get()
            ->keyBy('parameter_name');

        foreach ($parameters as $paramName => $value) {
            if ($value === null) {
                continue;
            }

            $threshold = $thresholds->get($paramName);
            if (!$threshold) {
                continue;
            }

            $violation = $threshold->checkValue((float) $value);
            if ($violation) {
                $alert = CbmAlert::create([
                    'threshold_id' => $threshold->id,
                    'checklist_id' => $checklistId,
                    'checklist_type' => $checklistType,
                    'parameter_name' => $paramName,
                    'recorded_value' => $value,
                    'threshold_value' => $violation['threshold'],
                    'alert_type' => $violation['type'],
                    'severity' => $violation['severity'],
                    'status' => 'open',
                ]);

                $alerts[] = [
                    'alert' => $alert,
                    'message' => $violation['message'],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get compliance summary for dashboard
     */
    public function getComplianceSummary(?int $areaId = null): array
    {
        // This month
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        
        // This week
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();

        // Today
        $today = now()->toDateString();

        $baseQuery = fn() => CbmExecution::query()
            ->join('cbm_schedules', 'cbm_executions.cbm_schedule_id', '=', 'cbm_schedules.id')
            ->where('cbm_schedules.is_active', true)
            ->when($areaId, fn($q) => $q->where('cbm_schedules.area_id', $areaId));

        // Monthly stats
        $monthlyStats = $baseQuery()
            ->whereBetween('cbm_executions.scheduled_date', [$thisMonthStart, $thisMonthEnd])
            ->selectRaw('
                COUNT(*) as scheduled,
                SUM(CASE WHEN is_executed = 1 THEN 1 ELSE 0 END) as executed
            ')
            ->first();

        // Weekly stats
        $weeklyStats = $baseQuery()
            ->whereBetween('cbm_executions.scheduled_date', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('
                COUNT(*) as scheduled,
                SUM(CASE WHEN is_executed = 1 THEN 1 ELSE 0 END) as executed
            ')
            ->first();

        // Today's stats
        $todayStats = $baseQuery()
            ->where('cbm_executions.scheduled_date', $today)
            ->selectRaw('
                COUNT(*) as scheduled,
                SUM(CASE WHEN is_executed = 1 THEN 1 ELSE 0 END) as executed
            ')
            ->first();

        // Open alerts
        $openAlerts = CbmAlert::where('status', 'open')->count();
        $criticalAlerts = CbmAlert::where('status', 'open')->where('severity', 'critical')->count();

        return [
            'monthly' => [
                'scheduled' => (int) $monthlyStats->scheduled,
                'executed' => (int) $monthlyStats->executed,
                'compliance' => $monthlyStats->scheduled > 0 
                    ? round(($monthlyStats->executed / $monthlyStats->scheduled) * 100, 1) 
                    : 0,
            ],
            'weekly' => [
                'scheduled' => (int) $weeklyStats->scheduled,
                'executed' => (int) $weeklyStats->executed,
                'compliance' => $weeklyStats->scheduled > 0 
                    ? round(($weeklyStats->executed / $weeklyStats->scheduled) * 100, 1) 
                    : 0,
            ],
            'today' => [
                'scheduled' => (int) $todayStats->scheduled,
                'executed' => (int) $todayStats->executed,
                'pending' => (int) $todayStats->scheduled - (int) $todayStats->executed,
            ],
            'alerts' => [
                'open' => $openAlerts,
                'critical' => $criticalAlerts,
            ],
        ];
    }
}
