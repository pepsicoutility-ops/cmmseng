<?php

namespace App\Filament\Widgets;

use App\Models\WoImprovement;
use App\Models\AreaOwner;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WoImprovementStatsWidget extends BaseWidget
{
    protected static ?int $sort = 11;

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Check if user is area owner
        $ownedAreas = AreaOwner::where('owner_gpid', $user->gpid)
            ->where('is_active', true)
            ->pluck('area_id')
            ->toArray();

        // Check user role for data filtering
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin']);

        // WO Improvements count for current month
        $monthlyImprovementsQuery = WoImprovement::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth);
        
        if (!$isManager && !empty($ownedAreas)) {
            // Filter by work orders in owned areas
            $monthlyImprovementsQuery->whereHas('workOrder', function($q) use ($ownedAreas) {
                $q->whereIn('area_id', $ownedAreas);
            });
        } elseif (!$isManager) {
            // Show only own improvements
            $monthlyImprovementsQuery->where('improved_by_gpid', $user->gpid);
        }

        $monthlyImprovements = $monthlyImprovementsQuery->count();
        $monthlyTarget = !empty($ownedAreas) ? count($ownedAreas) * 5 : 5;

        // Calculate percentage progress towards monthly target
        $monthlyProgress = $monthlyTarget > 0 ? round(($monthlyImprovements / $monthlyTarget) * 100, 1) : 0;
        $targetStatus = $monthlyProgress >= 100 ? '✅ Target Achieved!' : '⚠️ Below Target';
        $targetColor = $monthlyProgress >= 100 ? 'success' : ($monthlyProgress >= 80 ? 'warning' : 'danger');

        // Total improvements this year
        $yearlyImprovementsQuery = WoImprovement::whereYear('created_at', $currentYear);
        if (!$isManager && !empty($ownedAreas)) {
            $yearlyImprovementsQuery->whereHas('workOrder', function($q) use ($ownedAreas) {
                $q->whereIn('area_id', $ownedAreas);
            });
        } elseif (!$isManager) {
            $yearlyImprovementsQuery->where('improved_by_gpid', $user->gpid);
        }
        $yearlyImprovements = $yearlyImprovementsQuery->count();

        // Total time saved
        $timeSavedQuery = WoImprovement::whereYear('created_at', $currentYear);
        if (!$isManager && !empty($ownedAreas)) {
            $timeSavedQuery->whereHas('workOrder', function($q) use ($ownedAreas) {
                $q->whereIn('area_id', $ownedAreas);
            });
        } elseif (!$isManager) {
            $timeSavedQuery->where('improved_by_gpid', $user->gpid);
        }
        $totalTimeSaved = $timeSavedQuery->sum('time_saved_minutes') ?? 0;
        $timeSavedHours = round($totalTimeSaved / 60, 1);

        // Total cost saved
        $costSavedQuery = WoImprovement::whereYear('created_at', $currentYear);
        if (!$isManager && !empty($ownedAreas)) {
            $costSavedQuery->whereHas('workOrder', function($q) use ($ownedAreas) {
                $q->whereIn('area_id', $ownedAreas);
            });
        } elseif (!$isManager) {
            $costSavedQuery->where('improved_by_gpid', $user->gpid);
        }
        $totalCostSaved = $costSavedQuery->sum('cost_saved') ?? 0;

        // Recurrence prevented count
        $recurrencePreventedQuery = WoImprovement::whereYear('created_at', $currentYear)
            ->where('recurrence_prevented', true);
        if (!$isManager && !empty($ownedAreas)) {
            $recurrencePreventedQuery->whereHas('workOrder', function($q) use ($ownedAreas) {
                $q->whereIn('area_id', $ownedAreas);
            });
        } elseif (!$isManager) {
            $recurrencePreventedQuery->where('improved_by_gpid', $user->gpid);
        }
        $recurrencePrevented = $recurrencePreventedQuery->count();

        return [
            Stat::make('Monthly Improvements', $monthlyImprovements . ' / ' . $monthlyTarget)
                ->description($targetStatus . ' (' . $monthlyProgress . '%)')
                ->descriptionIcon($monthlyProgress >= 100 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($targetColor)
                ->chart($this->getMonthlyTrend()),

            Stat::make('Yearly Improvements', $yearlyImprovements)
                ->description('Total improvements this year')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make('Time Saved', $timeSavedHours . ' Hours')
                ->description('From ' . $totalTimeSaved . ' minutes saved')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),

            Stat::make('Impact', 'Rp ' . number_format($totalCostSaved, 0, ',', '.'))
                ->description($recurrencePrevented . ' issues prevented from recurring')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),
        ];
    }

    protected function getMonthlyTrend(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin']);

        $ownedAreas = AreaOwner::where('owner_gpid', $user->gpid)
            ->where('is_active', true)
            ->pluck('area_id')
            ->toArray();

        $monthlyData = WoImprovement::whereYear('created_at', $currentYear)
            ->when(!$isManager && !empty($ownedAreas), function($q) use ($ownedAreas) {
                $q->whereHas('workOrder', function($q) use ($ownedAreas) {
                    $q->whereIn('area_id', $ownedAreas);
                });
            })
            ->when(!$isManager && empty($ownedAreas), function($q) use ($user) {
                $q->where('improved_by_gpid', $user->gpid);
            })
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill in missing months with 0
        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend[] = $monthlyData[$i] ?? 0;
        }

        return $trend;
    }
}
