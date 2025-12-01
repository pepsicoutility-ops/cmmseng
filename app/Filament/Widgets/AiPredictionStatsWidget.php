<?php

namespace App\Filament\Widgets;

use App\Models\EquipmentPrediction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AiPredictionStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s'; // Refresh every 60 seconds
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        // Get latest predictions for each equipment type
        $chillerStats = $this->getEquipmentStats('chiller1', 'chiller2');
        $compressorStats = $this->getEquipmentStats('compressor1', 'compressor2');
        $ahuStats = $this->getEquipmentStats('ahu');

        // Overall anomaly count today
        $totalAnomalies = EquipmentPrediction::whereDate('predicted_at', $today)
            ->where('is_anomaly', true)
            ->count();

        // Critical risk count
        $criticalCount = EquipmentPrediction::whereDate('predicted_at', $today)
            ->where('risk_signal', 'critical')
            ->count();

        // High priority equipment
        $highPriority = EquipmentPrediction::whereDate('predicted_at', $today)
            ->where('equipment_priority', '>=', 8)
            ->count();

        return [
            Stat::make('AI Anomalies Detected', $totalAnomalies)
                ->description('Equipment anomalies found today')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($totalAnomalies > 0 ? 'danger' : 'success')
                ->chart($this->getAnomalyTrend()),

            Stat::make('Critical Risk Signals', $criticalCount)
                ->description('Equipment requiring immediate attention')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($criticalCount > 0 ? 'danger' : 'success'),

            Stat::make('High Priority Equipment', $highPriority)
                ->description('Equipment priority ≥ 8/10')
                ->descriptionIcon('heroicon-o-flag')
                ->color($highPriority > 0 ? 'warning' : 'success'),

            Stat::make('Chiller Status', $chillerStats['status'])
                ->description($chillerStats['description'])
                ->descriptionIcon('heroicon-o-beaker')
                ->color($chillerStats['color']),

            Stat::make('Compressor Status', $compressorStats['status'])
                ->description($compressorStats['description'])
                ->descriptionIcon('heroicon-o-cpu-chip')
                ->color($compressorStats['color']),

            Stat::make('AHU Status', $ahuStats['status'])
                ->description($ahuStats['description'])
                ->descriptionIcon('heroicon-o-cloud')
                ->color($ahuStats['color']),
        ];
    }

    protected function getEquipmentStats(string ...$equipmentTypes): array
    {
        $anomalyCount = EquipmentPrediction::whereIn('equipment_type', $equipmentTypes)
            ->whereDate('predicted_at', now()->toDateString())
            ->where('is_anomaly', true)
            ->count();

        $criticalCount = EquipmentPrediction::whereIn('equipment_type', $equipmentTypes)
            ->whereDate('predicted_at', now()->toDateString())
            ->where('risk_signal', 'critical')
            ->count();

        if ($criticalCount > 0) {
            return [
                'status' => 'CRITICAL',
                'description' => "{$criticalCount} critical issue(s)",
                'color' => 'danger',
            ];
        }

        if ($anomalyCount > 0) {
            return [
                'status' => 'WARNING',
                'description' => "{$anomalyCount} anomaly(ies) detected",
                'color' => 'warning',
            ];
        }

        return [
            'status' => 'NORMAL',
            'description' => 'All systems operating normally',
            'color' => 'success',
        ];
    }

    protected function getAnomalyTrend(): array
    {
        return EquipmentPrediction::selectRaw('DATE(predicted_at) as date, COUNT(*) as count')
            ->where('is_anomaly', true)
            ->where('predicted_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'super_admin' ||
            $user->department === 'utility'
        );
    }
}