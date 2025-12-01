<?php

namespace App\Filament\Widgets;

use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EnergyMetricsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;
    
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Energy Performance - Last 7 Days';
    }

    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    protected function getData(): array
    {
        // Get data for the last 7 days
        $labels = [];
        $chiller1Temps = [];
        $chiller2Temps = [];
        $comp1Pressures = [];
        $comp2Pressures = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $labels[] = $date->format('M d');

            // Chiller 1 average evaporator temperature
            $chiller1Temps[] = Chiller1Checklist::whereDate('created_at', $date)
                ->avg('sat_evap_t') ?? 0;

            // Chiller 2 average evaporator temperature
            $chiller2Temps[] = Chiller2Checklist::whereDate('created_at', $date)
                ->avg('sat_evap_t') ?? 0;

            // Compressor 1 oil pressure
            $comp1Pressures[] = Compressor1Checklist::whereDate('created_at', $date)
                ->avg('bearing_oil_pressure') ?? 0;

            // Compressor 2 oil pressure
            $comp2Pressures[] = Compressor2Checklist::whereDate('created_at', $date)
                ->avg('bearing_oil_pressure') ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Chiller 1 Evap Temp (°C)',
                    'data' => $chiller1Temps,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Chiller 2 Evap Temp (°C)',
                    'data' => $chiller2Temps,
                    'borderColor' => 'rgb(147, 51, 234)',
                    'backgroundColor' => 'rgba(147, 51, 234, 0.1)',
                ],
                [
                    'label' => 'Comp 1 Oil Press (Bar)',
                    'data' => $comp1Pressures,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Comp 2 Oil Press (Bar)',
                    'data' => $comp2Pressures,
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
