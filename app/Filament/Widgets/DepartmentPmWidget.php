<?php

namespace App\Filament\Widgets;

use App\Models\PmSchedule;
use App\Models\PmExecution;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DepartmentPmWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'asisten_manager';
    }
    
    protected function getStats(): array
    {
        $user = Auth::user();
        $department = $user->department;
        
        // PM scheduled for this week in department
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        $scheduledThisWeek = PmExecution::whereHas('pmSchedule.asset', function ($query) use ($department) {
            $query->where('department', $department);
        })
        ->whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])
        ->count();
        
        // Completed PM this week in department
        $completedThisWeek = PmExecution::whereHas('pmSchedule.asset', function ($query) use ($department) {
            $query->where('department', $department);
        })
        ->whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])
        ->where('status', 'completed')
        ->count();
        
        // Overdue PM in department
        $overduePm = PmExecution::whereHas('pmSchedule.asset', function ($query) use ($department) {
            $query->where('department', $department);
        })
        ->where('scheduled_date', '<', Carbon::now())
        ->whereNotIn('status', ['completed'])
        ->count();
        
        // Pending PM (scheduled but not completed)
        $pendingPm = PmExecution::whereHas('pmSchedule.asset', function ($query) use ($department) {
            $query->where('department', $department);
        })
        ->where('scheduled_date', '>=', Carbon::now())
        ->where('status', 'pending')
        ->count();
        
        return [
            Stat::make('PM This Week', $scheduledThisWeek)
                ->description($department . ' department')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),
            Stat::make('Completed', $completedThisWeek)
                ->description('This week')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Overdue', $overduePm)
                ->description('Needs attention')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),
            Stat::make('Pending', $pendingPm)
                ->description('Upcoming')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
