<?php

namespace App\Filament\Widgets;

use App\Models\EquipmentTrouble;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TroubleStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $openTroubles = EquipmentTrouble::open()->count();
        $criticalTroubles = EquipmentTrouble::open()->critical()->count();
        $highTroubles = EquipmentTrouble::open()->high()->count();
        $resolvedToday = EquipmentTrouble::whereDate('resolved_at', today())->count();
        
        // Average response time for today
        $avgResponseTime = EquipmentTrouble::whereDate('acknowledged_at', today())
            ->get()
            ->pluck('response_time')
            ->filter()
            ->avg();
        
        // Average resolution time for today
        $avgResolutionTime = EquipmentTrouble::whereDate('resolved_at', today())
            ->get()
            ->pluck('resolution_time')
            ->filter()
            ->avg();

        return [
            Stat::make('Open Troubles', $openTroubles)
                ->description($criticalTroubles ? "{$criticalTroubles} Critical, {$highTroubles} High" : 'No critical issues')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($criticalTroubles > 0 ? 'danger' : ($openTroubles > 0 ? 'warning' : 'success')),
            
            Stat::make('Critical Equipment', $criticalTroubles)
                ->description('Requires immediate attention')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($criticalTroubles > 0 ? 'danger' : 'success'),
            
            Stat::make('Resolved Today', $resolvedToday)
                ->description('Equipment back online')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Avg Response Time', $avgResponseTime ? round($avgResponseTime) . ' min' : '-')
                ->description('Time to acknowledge')
                ->descriptionIcon('heroicon-o-clock')
                ->color($avgResponseTime && $avgResponseTime > 30 ? 'warning' : 'success'),
            
            Stat::make('Avg Resolution Time', $avgResolutionTime ? round($avgResolutionTime) . ' min' : '-')
                ->description('Time to resolve')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($avgResolutionTime && $avgResolutionTime > 120 ? 'warning' : 'success'),
        ];
    }
}
