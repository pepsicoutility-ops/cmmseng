<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DepartmentWoWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'asisten_manager';
    }
    
    protected function getStats(): array
    {
        $user = Auth::user();
        $department = $user->department;
        
        // WO in department this week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        $woThisWeek = WorkOrder::where('assign_to', $department)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();
        
        // Open WO (not completed/closed)
        $openWo = WorkOrder::where('assign_to', $department)
            ->whereNotIn('status', ['completed', 'closed'])
            ->count();
        
        // Avg response time in hours
        $avgResponseTime = WorkOrder::where('assign_to', $department)
            ->whereNotNull('reviewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->value('avg_hours');
        
        // Total WO handled by department
        $totalWo = WorkOrder::where('assign_to', $department)->count();
        
        return [
            Stat::make('WO This Week', $woThisWeek)
                ->description($department . ' department')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('primary'),
            Stat::make('Open WO', $openWo)
                ->description('Needs action')
                ->descriptionIcon('heroicon-o-folder-open')
                ->color('warning'),
            Stat::make('Avg Response Time', round($avgResponseTime ?? 0, 1) . ' hrs')
                ->description('Time to review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Total WO', $totalWo)
                ->description('All time')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
        ];
    }
}
