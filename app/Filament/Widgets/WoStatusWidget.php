<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WoStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }
    
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Base query - filter by department for asisten_manager
        $query = WorkOrder::query();
        if ($user->role === 'asisten_manager') {
            $query->where('assign_to', $user->department);
        }
        
        // Count by status
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $onHold = (clone $query)->where('status', 'on_hold')->count();
        
        return [
            Stat::make('Submitted', $submitted)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
            Stat::make('In Progress', $inProgress)
                ->description('Currently working')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
            Stat::make('Completed', $completed)
                ->description('Work finished')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('On Hold', $onHold)
                ->description('Temporarily paused')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('danger'),
        ];
    }
}
