<?php

namespace App\Services;

use App\Models\ProductionRecord;
use App\Models\UtilityConsumption;
use App\Models\UtilityTarget;
use App\Models\UtilityPerKgReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UtilityCostService
{
    /**
     * Calculate utility per kg for a period
     */
    public function calculateUtilityPerKg(
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $areaId = null
    ): ?UtilityPerKgReport {
        // Get production data
        $productionQuery = ProductionRecord::query()
            ->where('status', 'approved')
            ->whereBetween('production_date', [$periodStart, $periodEnd]);

        if ($areaId) {
            $productionQuery->where('area_id', $areaId);
        }

        $totalProduction = $productionQuery->sum('weight_kg');

        if ($totalProduction <= 0) {
            return null; // No production data
        }

        // Get utility consumption data
        $utilityQuery = UtilityConsumption::query()
            ->where('status', 'approved')
            ->whereBetween('consumption_date', [$periodStart, $periodEnd]);

        if ($areaId) {
            $utilityQuery->where('area_id', $areaId);
        }

        $utilities = $utilityQuery->selectRaw('
            SUM(water_consumption) as total_water,
            SUM(electricity_consumption) as total_electricity,
            SUM(gas_consumption) as total_gas
        ')->first();

        $totalWater = (float) ($utilities->total_water ?? 0);
        $totalElectricity = (float) ($utilities->total_electricity ?? 0);
        $totalGas = (float) ($utilities->total_gas ?? 0);

        // Calculate per kg
        $waterPerKg = $totalProduction > 0 ? round($totalWater / $totalProduction, 4) : 0;
        $electricityPerKg = $totalProduction > 0 ? round($totalElectricity / $totalProduction, 4) : 0;
        $gasPerKg = $totalProduction > 0 ? round($totalGas / $totalProduction, 4) : 0;

        // Get targets
        $year = $periodStart->year;
        $waterTarget = UtilityTarget::getCurrentTarget('water', $year, $areaId);
        $electricityTarget = UtilityTarget::getCurrentTarget('electricity', $year, $areaId);
        $gasTarget = UtilityTarget::getCurrentTarget('gas', $year, $areaId);

        // Calculate status
        $waterStatus = $waterTarget ? $this->calculateStatus($waterPerKg, $waterTarget) : null;
        $electricityStatus = $electricityTarget ? $this->calculateStatus($electricityPerKg, $electricityTarget) : null;
        $gasStatus = $gasTarget ? $this->calculateStatus($gasPerKg, $gasTarget) : null;

        return UtilityPerKgReport::updateOrCreate(
            [
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'area_id' => $areaId,
            ],
            [
                'period_end' => $periodEnd,
                'total_production_kg' => $totalProduction,
                'total_water_liters' => $totalWater,
                'total_electricity_kwh' => $totalElectricity,
                'total_gas_kwh' => $totalGas,
                'water_per_kg' => $waterPerKg,
                'electricity_per_kg' => $electricityPerKg,
                'gas_per_kg' => $gasPerKg,
                'water_target' => $waterTarget?->target_per_kg,
                'electricity_target' => $electricityTarget?->target_per_kg,
                'gas_target' => $gasTarget?->target_per_kg,
                'water_status' => $waterStatus,
                'electricity_status' => $electricityStatus,
                'gas_status' => $gasStatus,
            ]
        );
    }

    /**
     * Calculate status based on target
     */
    protected function calculateStatus(float $value, UtilityTarget $target): string
    {
        $result = $target->checkValue($value);
        return $result['status'];
    }

    /**
     * Get utility summary for dashboard
     */
    public function getUtilitySummary(?int $areaId = null): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // This month production
        $thisMonthProduction = ProductionRecord::query()
            ->where('status', 'approved')
            ->whereMonth('production_date', now()->month)
            ->whereYear('production_date', now()->year)
            ->when($areaId, fn($q) => $q->where('area_id', $areaId))
            ->sum('weight_kg');

        // This month utilities
        $thisMonthUtilities = UtilityConsumption::query()
            ->where('status', 'approved')
            ->whereMonth('consumption_date', now()->month)
            ->whereYear('consumption_date', now()->year)
            ->when($areaId, fn($q) => $q->where('area_id', $areaId))
            ->selectRaw('
                SUM(water_consumption) as water,
                SUM(electricity_consumption) as electricity,
                SUM(gas_consumption) as gas
            ')
            ->first();

        // Calculate per kg this month
        $waterPerKg = $thisMonthProduction > 0 
            ? round(($thisMonthUtilities->water ?? 0) / $thisMonthProduction, 4) 
            : 0;
        $electricityPerKg = $thisMonthProduction > 0 
            ? round(($thisMonthUtilities->electricity ?? 0) / $thisMonthProduction, 4) 
            : 0;
        $gasPerKg = $thisMonthProduction > 0 
            ? round(($thisMonthUtilities->gas ?? 0) / $thisMonthProduction, 4) 
            : 0;

        // Get targets
        $year = now()->year;
        $waterTarget = UtilityTarget::getCurrentTarget('water', $year, $areaId);
        $electricityTarget = UtilityTarget::getCurrentTarget('electricity', $year, $areaId);
        $gasTarget = UtilityTarget::getCurrentTarget('gas', $year, $areaId);

        return [
            'production' => [
                'this_month' => round($thisMonthProduction, 2),
                'unit' => 'kg',
            ],
            'water' => [
                'consumption' => round($thisMonthUtilities->water ?? 0, 2),
                'per_kg' => $waterPerKg,
                'target' => $waterTarget?->target_per_kg,
                'unit' => 'L/kg',
                'status' => $waterTarget ? $waterTarget->checkValue($waterPerKg)['status'] : null,
            ],
            'electricity' => [
                'consumption' => round($thisMonthUtilities->electricity ?? 0, 2),
                'per_kg' => $electricityPerKg,
                'target' => $electricityTarget?->target_per_kg,
                'unit' => 'kWh/kg',
                'status' => $electricityTarget ? $electricityTarget->checkValue($electricityPerKg)['status'] : null,
            ],
            'gas' => [
                'consumption' => round($thisMonthUtilities->gas ?? 0, 2),
                'per_kg' => $gasPerKg,
                'target' => $gasTarget?->target_per_kg,
                'unit' => 'kWh/kg',
                'status' => $gasTarget ? $gasTarget->checkValue($gasPerKg)['status'] : null,
            ],
        ];
    }

    /**
     * Get daily trend data for charts
     */
    public function getDailyTrend(int $days = 30, ?int $areaId = null): array
    {
        $startDate = now()->subDays($days);
        
        $data = DB::table('utility_per_kg_reports')
            ->where('period_type', 'daily')
            ->where('period_start', '>=', $startDate)
            ->when($areaId, fn($q) => $q->where('area_id', $areaId))
            ->orderBy('period_start')
            ->get();

        return [
            'labels' => $data->pluck('period_start')->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
            'water' => $data->pluck('water_per_kg')->toArray(),
            'electricity' => $data->pluck('electricity_per_kg')->toArray(),
            'gas' => $data->pluck('gas_per_kg')->toArray(),
            'production' => $data->pluck('total_production_kg')->toArray(),
        ];
    }

    /**
     * Check if any utility exceeds target and needs attention
     */
    public function getAlerts(?int $areaId = null): array
    {
        $summary = $this->getUtilitySummary($areaId);
        $alerts = [];

        foreach (['water', 'electricity', 'gas'] as $utility) {
            if (isset($summary[$utility]['status']) && $summary[$utility]['status'] === 'exceeded') {
                $alerts[] = [
                    'type' => $utility,
                    'message' => ucfirst($utility) . " consumption ({$summary[$utility]['per_kg']} {$summary[$utility]['unit']}) exceeds target ({$summary[$utility]['target']} {$summary[$utility]['unit']})",
                    'severity' => 'warning',
                ];
            }
        }

        return $alerts;
    }
}
