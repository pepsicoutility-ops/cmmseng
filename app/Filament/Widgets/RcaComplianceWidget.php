<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\RootCauseAnalysis;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;

class RcaComplianceWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 8;

    protected function getStats(): array
    {
        // WO requiring RCA
        $woRequiringRca = WorkOrder::where('rca_required', true)->count();
        $woWithCompletedRca = WorkOrder::where('rca_required', true)
            ->where('rca_status', 'completed')
            ->count();
        
        $compliance = $woRequiringRca > 0 
            ? round(($woWithCompletedRca / $woRequiringRca) * 100, 1) 
            : 100;

        $complianceColor = $compliance >= 90 ? 'success' : ($compliance >= 75 ? 'warning' : 'danger');

        // Pending RCAs
        $pendingRcas = RootCauseAnalysis::pending()->count();
        $overdueRcas = RootCauseAnalysis::overdue()->count();

        // This month stats
        $thisMonth = RootCauseAnalysis::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        $createdThisMonth = (clone $thisMonth)->count();
        $completedThisMonth = (clone $thisMonth)->whereIn('status', ['approved', 'closed'])->count();

        // Root cause categories breakdown
        $topCategory = RootCauseAnalysis::selectRaw('root_cause_category, COUNT(*) as count')
            ->whereNotNull('root_cause_category')
            ->groupBy('root_cause_category')
            ->orderByDesc('count')
            ->first();

        return [
            Stat::make('RCA Compliance', $compliance . '%')
                ->description("Target: ≥90% | {$woWithCompletedRca}/{$woRequiringRca} completed")
                ->descriptionIcon($complianceColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($complianceColor)
                ->chart([75, 80, 85, 88, 90, 85, $compliance]),

            Stat::make('Pending RCAs', $pendingRcas)
                ->description($overdueRcas > 0 ? "{$overdueRcas} overdue" : 'No overdue')
                ->descriptionIcon($overdueRcas > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
                ->color($overdueRcas > 0 ? 'danger' : ($pendingRcas > 0 ? 'warning' : 'success')),

            Stat::make('This Month', "{$completedThisMonth}/{$createdThisMonth}")
                ->description('Completed / Created')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Top Root Cause', $topCategory ? ucfirst($topCategory->root_cause_category) : 'N/A')
                ->description($topCategory ? "{$topCategory->count} occurrences" : 'No data')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }
}
