<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use App\Models\PmExecution;
use App\Models\PmCompliance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
    
    protected function getStats(): array
    {
        // Total PM this week
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $pmThisWeek = PmExecution::whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])->count();
        
        // Total WO this week
        $woThisWeek = WorkOrder::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        
        // Average MTTR (completed WO only)
        $avgMttr = WorkOrder::where('status', 'completed')
            ->whereNotNull('mttr')
            ->avg('mttr');
        
        // Latest compliance percentage
        $compliance = PmCompliance::where('period', 'week')
            ->orderBy('period_end', 'desc')
            ->first();
        $compliancePercent = $compliance ? $compliance->compliance_percentage : 0;
        
        return [
            Stat::make('PM This Week', $pmThisWeek)
                ->description('Scheduled PM executions')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),
            Stat::make('WO This Week', $woThisWeek)
                ->description('Work orders created')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('info'),
            Stat::make('Average MTTR', $avgMttr ? number_format($avgMttr, 0) . ' min' : 'N/A')
                ->description('Mean Time To Repair')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('PM Compliance', number_format($compliancePercent, 1) . '%')
                ->description('This week')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($compliancePercent >= 95 ? 'success' : ($compliancePercent >= 85 ? 'warning' : 'danger')),
        ];
    }
}
