<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use App\Models\PmExecution;
use App\Models\PmCompliance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        // Cache stats for 5 minutes to reduce database load
        $stats = Cache::remember('dashboard.overview_stats', now()->addMinutes(5), function () {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            
            return [
                'pm_this_week' => PmExecution::whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])->count(),
                'wo_this_week' => WorkOrder::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
                'avg_mttr' => WorkOrder::where('status', 'completed')->whereNotNull('mttr')->avg('mttr'),
                'compliance' => PmCompliance::where('period', 'week')->orderBy('period_end', 'desc')->value('compliance_percentage') ?? 0,
            ];
        });
        
        $pmThisWeek = $stats['pm_this_week'];
        $woThisWeek = $stats['wo_this_week'];
        $avgMttr = $stats['avg_mttr'];
        $compliancePercent = $stats['compliance'];
        
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
