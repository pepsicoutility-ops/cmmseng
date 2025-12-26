<?php

namespace App\Filament\Widgets;

use App\Models\Kaizen;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KaizenStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Check user role for data filtering
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin']);

        // Kaizen count for current year (per person or all)
        $yearlyKaizenQuery = Kaizen::whereYear('created_at', $currentYear);
        if (!$isManager) {
            $yearlyKaizenQuery->where('submitted_by_gpid', $user->gpid);
        }
        $yearlyKaizens = $yearlyKaizenQuery->count();
        $yearlyTarget = $isManager ? Kaizen::distinct('submitted_by_gpid')->count() * 4 : 4;

        // Kaizen count for current month
        $monthlyKaizenQuery = Kaizen::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth);
        if (!$isManager) {
            $monthlyKaizenQuery->where('submitted_by_gpid', $user->gpid);
        }
        $monthlyKaizens = $monthlyKaizenQuery->count();

        // Total score for current year (only from CLOSED kaizens)
        $yearlyScoreQuery = Kaizen::whereYear('created_at', $currentYear)
            ->where('status', 'closed');
        if (!$isManager) {
            $yearlyScoreQuery->where('submitted_by_gpid', $user->gpid);
        }
        $yearlyScore = $yearlyScoreQuery->sum('score');

        // Status breakdown
        $statusBreakdown = Kaizen::query()
            ->when(!$isManager, fn($q) => $q->where('submitted_by_gpid', $user->gpid))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $pending = ($statusBreakdown['submitted'] ?? 0) + ($statusBreakdown['under_review'] ?? 0);
        $closed = $statusBreakdown['closed'] ?? 0;

        // Calculate percentage progress towards yearly target
        $yearlyProgress = $yearlyTarget > 0 ? round(($yearlyKaizens / $yearlyTarget) * 100, 1) : 0;
        $targetStatus = $yearlyProgress >= 100 ? '✅ Target Achieved!' : '⚠️ Below Target';
        $targetColor = $yearlyProgress >= 100 ? 'success' : ($yearlyProgress >= 75 ? 'warning' : 'danger');

        return [
            Stat::make('Yearly Kaizens', $yearlyKaizens . ' / ' . $yearlyTarget)
                ->description($targetStatus . ' (' . $yearlyProgress . '%)')
                ->descriptionIcon($yearlyProgress >= 100 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($targetColor)
                ->chart($this->getMonthlyTrend()),

            Stat::make('This Month', $monthlyKaizens)
                ->description('Kaizens submitted this month')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Total Score', $yearlyScore)
                ->description('From closed Kaizens only')
                ->descriptionIcon('heroicon-o-star')
                ->color('primary'),

            Stat::make('Status', $pending . ' Pending / ' . $closed . ' Closed')
                ->description('Workflow status breakdown')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('warning'),
        ];
    }

    protected function getMonthlyTrend(): array
    {
        $user = Auth::user();
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin']);
        $currentYear = now()->year;

        $monthlyData = Kaizen::whereYear('created_at', $currentYear)
            ->when(!$isManager, fn($q) => $q->where('submitted_by_gpid', $user->gpid))
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill in missing months with 0
        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend[] = $monthlyData[$i] ?? 0;
        }

        return $trend;
    }
}
