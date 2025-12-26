<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        $department = $user->role === 'asisten_manager' ? $user->department : 'all';
        
        // Cache stats per department for 5 minutes
        $cacheKey = "dashboard.wo_status.{$department}";
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $query = WorkOrder::query();
            if ($user->role === 'asisten_manager') {
                $query->where('assign_to', $user->department);
            }
            
            return [
                'submitted' => (clone $query)->where('status', 'submitted')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'on_hold' => (clone $query)->where('status', 'on_hold')->count(),
            ];
        });
        
        return [
            Stat::make('Submitted', $stats['submitted'])
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
            Stat::make('In Progress', $stats['in_progress'])
                ->description('Currently working')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
            Stat::make('Completed', $stats['completed'])
                ->description('Work finished')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('On Hold', $stats['on_hold'])
                ->description('Temporarily paused')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('danger'),
        ];
    }
}
