<?php

namespace App\Filament\Widgets;

use App\Models\Abnormality;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbnormalityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 11;

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Check user role
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin', 'supervisor']);

        // Monthly abnormalities found
        $monthlyQuery = Abnormality::whereYear('found_date', $currentYear)
            ->whereMonth('found_date', $currentMonth);
        if (!$isManager) {
            $monthlyQuery->where(function ($q) use ($user) {
                $q->where('reported_by', $user->gpid)
                  ->orWhere('assigned_to', $user->gpid);
            });
        }
        $monthlyCount = $monthlyQuery->count();

        // Monthly target: 5 per person per month
        $monthlyTarget = 5;
        $monthlyProgress = $monthlyTarget > 0 ? round(($monthlyCount / $monthlyTarget) * 100, 1) : 0;

        // Open abnormalities (need attention)
        $openCount = Abnormality::whereIn('status', [
            Abnormality::STATUS_OPEN,
            Abnormality::STATUS_ASSIGNED,
            Abnormality::STATUS_IN_PROGRESS
        ])
        ->when(!$isManager, fn($q) => $q->where(function ($query) use ($user) {
            $query->where('reported_by', $user->gpid)
                  ->orWhere('assigned_to', $user->gpid);
        }))
        ->count();

        // Overdue count
        $overdueCount = Abnormality::overdue()
            ->when(!$isManager, fn($q) => $q->where(function ($query) use ($user) {
                $query->where('reported_by', $user->gpid)
                      ->orWhere('assigned_to', $user->gpid);
            }))
            ->count();

        // Fix rate this month (fixed/verified/closed out of total)
        $fixedThisMonth = Abnormality::whereYear('found_date', $currentYear)
            ->whereMonth('found_date', $currentMonth)
            ->whereIn('status', [Abnormality::STATUS_FIXED, Abnormality::STATUS_VERIFIED, Abnormality::STATUS_CLOSED])
            ->when(!$isManager, fn($q) => $q->where(function ($query) use ($user) {
                $query->where('reported_by', $user->gpid)
                      ->orWhere('assigned_to', $user->gpid);
            }))
            ->count();
        $fixRate = $monthlyCount > 0 ? round(($fixedThisMonth / $monthlyCount) * 100, 1) : 0;

        // Severity breakdown
        $severityBreakdown = Abnormality::whereIn('status', [
                Abnormality::STATUS_OPEN,
                Abnormality::STATUS_ASSIGNED,
                Abnormality::STATUS_IN_PROGRESS
            ])
            ->when(!$isManager, fn($q) => $q->where(function ($query) use ($user) {
                $query->where('reported_by', $user->gpid)
                      ->orWhere('assigned_to', $user->gpid);
            }))
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $criticalHigh = ($severityBreakdown['critical'] ?? 0) + ($severityBreakdown['high'] ?? 0);

        return [
            Stat::make('Monthly Abnormalities', $monthlyCount . ' / ' . $monthlyTarget)
                ->description($monthlyProgress >= 100 ? '✅ Target Met' : '⚠️ Below Target')
                ->descriptionIcon($monthlyProgress >= 100 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($monthlyProgress >= 100 ? 'success' : 'warning')
                ->chart($this->getMonthlyTrend()),

            Stat::make('Open Items', $openCount)
                ->description($overdueCount > 0 ? "⚠️ {$overdueCount} Overdue!" : 'No overdue items')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-o-clock' : 'heroicon-o-check')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Fix Rate', $fixRate . '%')
                ->description('Fixed this month')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color($fixRate >= 80 ? 'success' : ($fixRate >= 50 ? 'warning' : 'danger')),

            Stat::make('Critical/High Priority', $criticalHigh)
                ->description('Requires immediate attention')
                ->descriptionIcon('heroicon-o-fire')
                ->color($criticalHigh > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getMonthlyTrend(): array
    {
        $user = Auth::user();
        $isManager = in_array($user->role, ['manager', 'asisten_manager', 'super_admin', 'supervisor']);
        $currentYear = now()->year;

        $monthlyData = Abnormality::whereYear('found_date', $currentYear)
            ->when(!$isManager, fn($q) => $q->where(function ($query) use ($user) {
                $query->where('reported_by', $user->gpid)
                      ->orWhere('assigned_to', $user->gpid);
            }))
            ->select(DB::raw('MONTH(found_date) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill all months with data (1-12)
        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend[] = $monthlyData[$i] ?? 0;
        }

        return $trend;
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->role !== 'operator';
    }
}
