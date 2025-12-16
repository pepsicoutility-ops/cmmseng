<?php

namespace App\Filament\Widgets;

use App\Models\Chiller2Checklist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Chiller2StatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';
    
   public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();
        
        $checklistsToday = Chiller2Checklist::whereDate('created_at', $today)->count();
        $avgEvapTemp = Chiller2Checklist::whereDate('created_at', $today)->avg('sat_evap_t') ?? 0;
        $avgDisSuperheat = Chiller2Checklist::whereDate('created_at', $today)->avg('dis_superheat') ?? 0;
        $avgEvapPressure = Chiller2Checklist::whereDate('created_at', $today)->avg('evap_p') ?? 0;
        $avgCondsPressure = Chiller2Checklist::whereDate('created_at', $today)->avg('conds_p') ?? 0;
        $avgMotorAmps = Chiller2Checklist::whereDate('created_at', $today)->avg('motor_amps') ?? 0;
        $avgMotorVolts = Chiller2Checklist::whereDate('created_at', $today)->avg('motor_volts') ?? 0;
        
        // Use DB::select to avoid MySQL strict mode issues with all aggregate queries
        $result = DB::select(
            "SELECT AVG((lcl / NULLIF(fla, 0)) * 100) as loading,
                    AVG(cooler_reff_small_temp_diff) as avg_cooler, 
                    AVG(cond_reff_small_temp_diff) as avg_cond 
             FROM chiller2_checklists 
             WHERE DATE(created_at) = ?",
            [$today]
        );
        
        $avgLoading = $result[0]->loading ?? 0;
        $avgCoolerTempDiff = $result[0]->avg_cooler ?? 0;
        $avgCondTempDiff = $result[0]->avg_cond ?? 0;
        $healthScore = $this->calculateHealthScore($today);

        return [
            Stat::make('Chiller 2 - Checklists Today', $checklistsToday)
                ->description('Total checklists completed today')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->chart($this->getWeeklyTrend()),
            
            Stat::make('Avg Evaporator Temp', number_format($avgEvapTemp, 2) . '°C')
                ->description('Average evaporator temperature today')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color($avgEvapTemp > 10 ? 'danger' : 'success'),
            
            Stat::make('Avg Discharge Superheat', number_format($avgDisSuperheat, 2) . '°C')
                ->description('Average discharge superheat today')
                ->descriptionIcon('heroicon-o-fire')
                ->color($avgDisSuperheat > 15 ? 'warning' : 'success'),
            
            Stat::make('Avg Evaporator Pressure', number_format($avgEvapPressure, 2) . ' kPa')
                ->description('Average evaporator pressure today')
                ->descriptionIcon('heroicon-o-arrow-down-circle')
                ->color('info'),
            
            Stat::make('Avg Condenser Pressure', number_format($avgCondsPressure, 2) . ' kPa')
                ->description('Average condenser pressure today')
                ->descriptionIcon('heroicon-o-arrow-up-circle')
                ->color('warning'),
            
            Stat::make('Motor Amps & Volts', number_format($avgMotorAmps, 1) . 'A / ' . number_format($avgMotorVolts, 1) . 'V')
                ->description('Average motor current & voltage')
                ->descriptionIcon('heroicon-o-bolt')
                ->color('primary'),
            
            Stat::make('Avg FLA Loading %', number_format($avgLoading, 1) . '%')
                ->description('Loading = (LCL / FLA) × 100')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($this->getLoadingColor($avgLoading)),
            
            Stat::make('Temp Diff (Cooler/Cond)', number_format($avgCoolerTempDiff, 2) . '°C / ' . number_format($avgCondTempDiff, 2) . '°C')
                ->description('Refrigerant small temp difference')
                ->descriptionIcon('heroicon-o-beaker')
                ->color('info'),
            
            Stat::make('Chiller 2 Health Score', number_format($healthScore, 0) . '/100')
                ->description($this->getHealthDescription($healthScore))
                ->descriptionIcon('heroicon-o-heart')
                ->color($this->getHealthColor($healthScore)),
        ];
    }

    protected function calculateHealthScore(string $date): float
    {
        $checklist = Chiller2Checklist::whereDate('created_at', $date)->latest()->first();
        if (!$checklist) return 0;

        $score = 0;

        // Temp/pressure within range (50 points)
        if ($checklist->sat_evap_t !== null) {
            if ($checklist->sat_evap_t >= 2 && $checklist->sat_evap_t <= 8) {
                $score += 15;
            } elseif ($checklist->sat_evap_t >= 0 && $checklist->sat_evap_t <= 10) {
                $score += 10;
            }
        }
        
        if ($checklist->evap_p !== null) {
            if ($checklist->evap_p >= 3 && $checklist->evap_p <= 6) {
                $score += 15;
            } elseif ($checklist->evap_p >= 2 && $checklist->evap_p <= 7) {
                $score += 10;
            }
        }
        
        if ($checklist->conds_p !== null) {
            if ($checklist->conds_p >= 10 && $checklist->conds_p <= 16) {
                $score += 20;
            } elseif ($checklist->conds_p >= 8 && $checklist->conds_p <= 18) {
                $score += 10;
            }
        }

        // Loading within 40-90% (30 points)
        if ($checklist->fla > 0) {
            $loading = ($checklist->lcl / $checklist->fla) * 100;
            if ($loading >= 40 && $loading <= 90) {
                $score += 30;
            } elseif ($loading >= 30 && $loading <= 95) {
                $score += 20;
            } elseif ($loading >= 20 && $loading <= 100) {
                $score += 10;
            }
        }

        // Refrigerant small temp diff within spec (20 points)
        if ($checklist->cooler_reff_small_temp_diff !== null) {
            if ($checklist->cooler_reff_small_temp_diff < 2) {
                $score += 10;
            } elseif ($checklist->cooler_reff_small_temp_diff < 3) {
                $score += 5;
            }
        }
        
        if ($checklist->cond_reff_small_temp_diff !== null) {
            if ($checklist->cond_reff_small_temp_diff < 2) {
                $score += 10;
            } elseif ($checklist->cond_reff_small_temp_diff < 3) {
                $score += 5;
            }
        }

        return min($score, 100);
    }

    protected function getWeeklyTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = Chiller2Checklist::whereDate('created_at', $date)->count();
        }
        return $trend;
    }

    protected function getLoadingColor(float $loading): string
    {
        if ($loading >= 40 && $loading <= 90) return 'success';
        elseif ($loading >= 30 && $loading <= 95) return 'warning';
        return 'danger';
    }

    protected function getHealthColor(float $score): string
    {
        if ($score >= 80) return 'success';
        elseif ($score >= 60) return 'warning';
        return 'danger';
    }

    protected function getHealthDescription(float $score): string
    {
        if ($score >= 80) return 'Excellent condition';
        elseif ($score >= 60) return 'Good condition, minor attention needed';
        elseif ($score >= 40) return 'Fair condition, maintenance required';
        return 'Poor condition, immediate action needed';
    }
}
