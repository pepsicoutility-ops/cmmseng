<?php

namespace App\Filament\Widgets;

use App\Models\Compressor2Checklist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class Compressor2StatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();
        
        $checklistsToday = Compressor2Checklist::whereDate('created_at', $today)->count();
        
        $avgBearingOilTemp = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('bearing_oil_temperature') ?? 0;
        
        $avgBearingOilPressure = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('bearing_oil_pressure') ?? 0;
        
        $avgDischargePressure = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('discharge_pressure') ?? 0;
        
        $avgDischargeTemp = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('discharge_temperature') ?? 0;
        
        $avgCoolingDeltaT = Compressor2Checklist::whereDate('created_at', $today)
            ->selectRaw('AVG(cws_temperature - cwr_temperature) as delta_t')
            ->value('delta_t') ?? 0;
        
        $avgRefrigerantPressure = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('refrigerant_pressure') ?? 0;
        
        $avgDewPoint = Compressor2Checklist::whereDate('created_at', $today)
            ->avg('dew_point') ?? 0;
        
        $abnormalCount = Compressor2Checklist::where('created_at', '>=', now()->subDays(7))
            ->where(function($query) {
                $query->where('notes', 'like', '%abnormal%')
                      ->orWhere('notes', 'like', '%warning%')
                      ->orWhere('notes', 'like', '%alarm%')
                      ->orWhere('notes', 'like', '%high%')
                      ->orWhere('notes', 'like', '%low%')
                      ->orWhere('notes', 'like', '%issue%');
            })
            ->count();

        return [
            Stat::make('Compressor 2 - Checklists Today', $checklistsToday)
                ->description('Total checklists completed today')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->chart($this->getWeeklyTrend()),
            
            Stat::make('Avg Bearing Oil Temp', number_format($avgBearingOilTemp, 2) . '°C')
                ->description('Average bearing oil temperature today')
                ->descriptionIcon('heroicon-o-fire')
                ->color($avgBearingOilTemp > 60 ? 'danger' : 'success'),
            
            Stat::make('Avg Bearing Oil Pressure', number_format($avgBearingOilPressure, 2) . ' Bar')
                ->description('Average bearing oil pressure today')
                ->descriptionIcon('heroicon-o-beaker')
                ->color($avgBearingOilPressure < 1.5 ? 'danger' : 'success'),
            
            Stat::make('Discharge Press & Temp', number_format($avgDischargePressure, 2) . ' Bar / ' . number_format($avgDischargeTemp, 1) . '°C')
                ->description('Average discharge pressure & temperature')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),
            
            Stat::make('Avg Cooling Delta-T', number_format($avgCoolingDeltaT, 2) . '°C')
                ->description('CWS - CWR temperature difference')
                ->descriptionIcon('heroicon-o-arrows-up-down')
                ->color($avgCoolingDeltaT < 3 ? 'warning' : 'success'),
            
            Stat::make('Avg Refrigerant Pressure', number_format($avgRefrigerantPressure, 2) . ' Bar')
                ->description('Average refrigerant pressure today')
                ->descriptionIcon('heroicon-o-cloud')
                ->color('primary'),
            
            Stat::make('Dew Point Average', number_format($avgDewPoint, 2) . '°C')
                ->description('Average dew point today')
                ->descriptionIcon('heroicon-o-beaker')
                ->color($avgDewPoint > 5 ? 'danger' : 'success'),
            
            Stat::make('Abnormal Count (7 Days)', $abnormalCount)
                ->description('Issues detected in last 7 days')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($abnormalCount > 3 ? 'danger' : ($abnormalCount > 0 ? 'warning' : 'success')),
        ];
    }

    protected function getWeeklyTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = Compressor2Checklist::whereDate('created_at', $date)->count();
        }
        return $trend;
    }
}
