<?php

namespace App\Filament\Widgets;

use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use App\Models\AhuChecklist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UtilityPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Get today's checklist counts
        $chiller1Today = Chiller1Checklist::whereDate('created_at', $today)->count();
        $chiller2Today = Chiller2Checklist::whereDate('created_at', $today)->count();
        $comp1Today = Compressor1Checklist::whereDate('created_at', $today)->count();
        $comp2Today = Compressor2Checklist::whereDate('created_at', $today)->count();
        $ahuToday = AhuChecklist::whereDate('created_at', $today)->count();
        
        $totalToday = $chiller1Today + $chiller2Today + $comp1Today + $comp2Today + $ahuToday;
        
        // Get this month's counts
        $chiller1Month = Chiller1Checklist::where('created_at', '>=', $thisMonth)->count();
        $chiller2Month = Chiller2Checklist::where('created_at', '>=', $thisMonth)->count();
        $comp1Month = Compressor1Checklist::where('created_at', '>=', $thisMonth)->count();
        $comp2Month = Compressor2Checklist::where('created_at', '>=', $thisMonth)->count();
        $ahuMonth = AhuChecklist::where('created_at', '>=', $thisMonth)->count();
        
        $totalMonth = $chiller1Month + $chiller2Month + $comp1Month + $comp2Month + $ahuMonth;

        // Calculate energy metrics (using actual column names)
        $avgChiller1Temp = Chiller1Checklist::whereDate('created_at', $today)
            ->avg('sat_evap_t') ?? 0;
        
        $avgChiller2Temp = Chiller2Checklist::whereDate('created_at', $today)
            ->avg('sat_evap_t') ?? 0;

        // Compliance rate (checklists completed vs expected)
        // Expected: 5 equipment * 3 checks per day = 15
        $expectedDaily = 15;
        $complianceRate = $expectedDaily > 0 ? round(($totalToday / $expectedDaily) * 100, 1) : 0;

        return [
            Stat::make('Checklists Today', $totalToday)
                ->description("Chiller 1: {$chiller1Today} | Chiller 2: {$chiller2Today} | Comp 1: {$comp1Today} | Comp 2: {$comp2Today} | AHU: {$ahuToday}")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($totalToday >= 10 ? 'success' : 'warning')
                ->chart(array_fill(0, 7, rand(8, 15))),

            Stat::make('This Month Total', $totalMonth)
                ->description('All utility checklists this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart(array_fill(0, 7, rand(200, 400))),

            Stat::make('Daily Compliance', $complianceRate . '%')
                ->description("Target: {$expectedDaily} checklists/day")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($complianceRate >= 80 ? 'success' : ($complianceRate >= 60 ? 'warning' : 'danger'))
                ->chart(array_fill(0, 7, rand(70, 100))),

            Stat::make('Avg Evaporator Temp', round(($avgChiller1Temp + $avgChiller2Temp) / 2, 1) . '°C')
                ->description('Chiller 1 & 2 average evaporator temp today')
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary')
                ->chart(array_fill(0, 7, rand(5, 15))),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
