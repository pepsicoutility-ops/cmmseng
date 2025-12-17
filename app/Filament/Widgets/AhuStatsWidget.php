<?php

namespace App\Filament\Widgets;

use App\Models\AhuChecklist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AhuStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();
        
        // Count PF, MF, HF across all AHU/PAU/VRF fields
        $pfFields = [
            'ahu_mb_1_1_pf', 'ahu_mb_1_2_pf', 'ahu_mb_1_3_pf',
            'pau_mb_1_pf', 'pau_mb_pr_1a_pf', 'pau_mb_pr_1b_pf', 'pau_mb_pr_1c_pf',
            'ahu_vrf_mb_ms_1a_pf', 'ahu_vrf_mb_ms_1b_pf', 'ahu_vrf_mb_ms_1c_pf',
            'ahu_vrf_mb_ss_1a_pf', 'ahu_vrf_mb_ss_1b_pf', 'ahu_vrf_mb_ss_1c_pf',
            'if_pre_filter_a', 'if_pre_filter_b', 'if_pre_filter_c',
            'if_pre_filter_d', 'if_pre_filter_e', 'if_pre_filter_f'
        ];
        
        $mfFields = [
            'ahu_mb_1_1_mf', 'ahu_mb_1_2_mf', 'ahu_mb_1_3_mf',
            'pau_mb_pr_1a_mf', 'pau_mb_pr_1b_mf', 'pau_mb_pr_1c_mf',
            'if_medium_a', 'if_medium_b', 'if_medium_c',
            'if_medium_d', 'if_medium_e', 'if_medium_f'
        ];
        
        $hfFields = [
            'ahu_mb_1_1_hf', 'ahu_mb_1_2_hf', 'ahu_mb_1_3_hf',
            'pau_mb_pr_1a_hf', 'pau_mb_pr_1b_hf', 'pau_mb_pr_1c_hf',
            'if_hepa_a', 'if_hepa_b', 'if_hepa_c',
            'if_hepa_d', 'if_hepa_e', 'if_hepa_f'
        ];
        
        // Total PF that need replacement (≥100) today
        $totalPfWarning = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($pfFields) {
                return collect($pfFields)->filter(function($field) use ($record) {
                    $value = (int)($record->$field ?? 0);
                    return $value >= 100 && $value <= 150;
                })->count();
            });
        
        $totalPfDanger = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($pfFields) {
                return collect($pfFields)->filter(function($field) use ($record) {
                    return (int)($record->$field ?? 0) > 150;
                })->count();
            });
        
        // Total MF that need replacement (≥200) today
        $totalMfWarning = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($mfFields) {
                return collect($mfFields)->filter(function($field) use ($record) {
                    $value = (int)($record->$field ?? 0);
                    return $value >= 200 && $value <= 250;
                })->count();
            });
        
        $totalMfDanger = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($mfFields) {
                return collect($mfFields)->filter(function($field) use ($record) {
                    return (int)($record->$field ?? 0) > 250;
                })->count();
            });
        
        // Total HF that need replacement (≥400) today
        $totalHfWarning = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($hfFields) {
                return collect($hfFields)->filter(function($field) use ($record) {
                    $value = (int)($record->$field ?? 0);
                    return $value >= 400 && $value <= 450;
                })->count();
            });
        
        $totalHfDanger = AhuChecklist::whereDate('created_at', $today)
            ->get()
            ->sum(function($record) use ($hfFields) {
                return collect($hfFields)->filter(function($field) use ($record) {
                    return (int)($record->$field ?? 0) > 450;
                })->count();
            });
        
        // Worst 5 AHU points (most HF in last 30 days)
        $worst5 = $this->getWorstAhuPoints();

        return [
            Stat::make('PF Need Attention', ($totalPfWarning + $totalPfDanger))
                ->description(new \Illuminate\Support\HtmlString(
                    '<span style="color: #f59e0b; font-weight: 600;">WARNING: ' . $totalPfWarning . '</span> | ' .
                    '<span style="color: #ef4444; font-weight: 600;">DANGER: ' . $totalPfDanger . '</span>'
                ))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($totalPfDanger > 0 ? 'danger' : ($totalPfWarning > 0 ? 'warning' : 'success'))
                ->chart($this->getPfTrend()),
            
            Stat::make('MF Need Attention', ($totalMfWarning + $totalMfDanger))
                ->description(new \Illuminate\Support\HtmlString(
                    '<span style="color: #f59e0b; font-weight: 600;">WARNING: ' . $totalMfWarning . '</span> | ' .
                    '<span style="color: #ef4444; font-weight: 600;">DANGER: ' . $totalMfDanger . '</span>'
                ))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($totalMfDanger > 0 ? 'danger' : ($totalMfWarning > 0 ? 'warning' : 'success')),
            
            Stat::make('HF Need Attention', ($totalHfWarning + $totalHfDanger))
                ->description(new \Illuminate\Support\HtmlString(
                    '<span style="color: #f59e0b; font-weight: 600;">WARNING: ' . $totalHfWarning . '</span> | ' .
                    '<span style="color: #ef4444; font-weight: 600;">DANGER: ' . $totalHfDanger . '</span>'
                ))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($totalHfDanger > 0 ? 'danger' : ($totalMfWarning > 0 ? 'warning' : 'success')),
            
            Stat::make('Worst AHU Point #1', $worst5[0]['name'] ?? 'N/A')
                ->description(($worst5[0]['count'] ?? 0) . ' times exceed threshold')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),
            
            Stat::make('Worst AHU Point #2', $worst5[1]['name'] ?? 'N/A')
                ->description(($worst5[1]['count'] ?? 0) . ' times exceed threshold')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),
            
            Stat::make('Worst AHU Point #3', $worst5[2]['name'] ?? 'N/A')
                ->description(($worst5[2]['count'] ?? 0) . ' times exceed threshold')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning'),
            
            Stat::make('Worst AHU Point #4', $worst5[3]['name'] ?? 'N/A')
                ->description(($worst5[3]['count'] ?? 0) . ' times exceed threshold')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning'),
            
            Stat::make('Worst AHU Point #5', $worst5[4]['name'] ?? 'N/A')
                ->description(($worst5[4]['count'] ?? 0) . ' times exceed threshold')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning'),
        ];
    }

    protected function getWorstAhuPoints(): array
    {
        $hfFields = [
            'ahu_mb_1_1_hf' => 'AHU MB 1.1',
            'ahu_mb_1_2_hf' => 'AHU MB 1.2',
            'ahu_mb_1_3_hf' => 'AHU MB 1.3',
            'pau_mb_pr_1a_hf' => 'PAU MB PR 1A',
            'pau_mb_pr_1b_hf' => 'PAU MB PR 1B',
            'pau_mb_pr_1c_hf' => 'PAU MB PR 1C',
            'if_hepa_a' => 'IF HEPA A',
            'if_hepa_b' => 'IF HEPA B',
            'if_hepa_c' => 'IF HEPA C',
            'if_hepa_d' => 'IF HEPA D',
            'if_hepa_e' => 'IF HEPA E',
            'if_hepa_f' => 'IF HEPA F',
        ];
        
        $records = AhuChecklist::where('created_at', '>=', now()->subDays(30))->get();
        
        $counts = [];
        foreach ($hfFields as $field => $name) {
            // Count how many times this field exceeded 400 (warning threshold)
            $counts[$name] = $records->filter(function($record) use ($field) {
                return (int)($record->$field ?? 0) >= 400;
            })->count();
        }
        
        arsort($counts);
        
        $worst = [];
        $rank = 0;
        foreach ($counts as $name => $count) {
            if ($rank >= 5) break;
            $worst[] = ['name' => $name, 'count' => $count];
            $rank++;
        }
        
        // Fill remaining slots with N/A
        while (count($worst) < 5) {
            $worst[] = ['name' => 'N/A', 'count' => 0];
        }
        
        return $worst;
    }

    protected function getPfTrend(): array
    {
        $pfFields = [
            'ahu_mb_1_1_pf', 'ahu_mb_1_2_pf', 'ahu_mb_1_3_pf',
            'pau_mb_1_pf', 'pau_mb_pr_1a_pf', 'pau_mb_pr_1b_pf', 'pau_mb_pr_1c_pf',
            'ahu_vrf_mb_ms_1a_pf', 'ahu_vrf_mb_ms_1b_pf', 'ahu_vrf_mb_ms_1c_pf',
            'ahu_vrf_mb_ss_1a_pf', 'ahu_vrf_mb_ss_1b_pf', 'ahu_vrf_mb_ss_1c_pf',
            'if_pre_filter_a', 'if_pre_filter_b', 'if_pre_filter_c',
            'if_pre_filter_d', 'if_pre_filter_e', 'if_pre_filter_f'
        ];
        
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $total = AhuChecklist::whereDate('created_at', $date)
                ->get()
                ->sum(function($record) use ($pfFields) {
                    return collect($pfFields)->filter(function($field) use ($record) {
                        return (int)($record->$field ?? 0) >= 100;
                    })->count();
                });
            $trend[] = $total;
        }
        return $trend;
    }
}
