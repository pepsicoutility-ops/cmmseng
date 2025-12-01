<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PmExecution;
use App\Models\WorkOrder;
use App\Models\Part;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CostAnalysisWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 8;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return in_array($user->role, ['super_admin', 'manager']);
    }
    
    protected function getStats(): array
    {
        // PM Cost (this month) - from pm_costs table via relationship
        $pmExecutions = PmExecution::whereMonth('actual_end', now()->month)
            ->whereYear('actual_end', now()->year)
            ->whereNotNull('actual_end')
            ->pluck('id');
        
        $pmCost = \App\Models\PmCost::whereIn('pm_execution_id', $pmExecutions)
            ->sum('total_cost');
            
        // WO Cost (this month) - from wo_costs table via relationship
        $workOrders = WorkOrder::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('id');
        
        $woCost = \App\Models\WoCost::whereIn('work_order_id', $workOrders)
            ->sum('total_cost');
            
        // Parts Value (current inventory)
        $partsValue = Part::sum(DB::raw('current_stock * unit_price'));
        
        // Total Maintenance Cost (this month)
        $totalCost = $pmCost + $woCost;
        
        return [
            Stat::make('PM Cost (This Month)', 'Rp ' . number_format($pmCost, 0, ',', '.'))
                ->description('Total PM execution costs')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('success'),
                
            Stat::make('WO Cost (This Month)', 'Rp ' . number_format($woCost, 0, ',', '.'))
                ->description('Total work order costs')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('warning'),
                
            Stat::make('Inventory Value', 'Rp ' . number_format($partsValue, 0, ',', '.'))
                ->description('Current parts stock value')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),
                
            Stat::make('Total Maintenance Cost', 'Rp ' . number_format($totalCost, 0, ',', '.'))
                ->description('PM + WO costs this month')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($totalCost > 50000000 ? 'danger' : 'success'),
        ];
    }
}
