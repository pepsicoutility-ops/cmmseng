<?php

namespace App\Filament\Widgets;

use App\Models\Chiller2Checklist;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class Chiller2Trend extends ChartWidget
{
    protected ?string $heading = 'Chiller 2 - Trend (Last 20 Records)';
    protected int|string|array $columnSpan = 'full';

    public static function canViewAny(): bool
    {
        // Only shown inside UtilityPerformanceAnalysis
        return false;
    }

    protected function getRecords(): Collection
    {
        return Chiller2Checklist::query()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    protected function getData(): array
    {
        $records = $this->getRecords();

        return [
            'datasets' => [
                [
                    'label'           => 'Evap Temp (°C)',
                    'data'            => $records->pluck('sat_evap_t'),
                    'borderColor'     => '#009FDA', // light blue
                    'backgroundColor' => 'rgba(0, 159, 218, 0.16)',
                    'fill'            => true,
                    'tension'         => 0.45,      // smooth curve
                    'borderWidth'     => 2,
                ],
                [
                    'label'           => 'Cond Pressure (kPa)',
                    'data'            => $records->pluck('conds_p'),
                    'borderColor'     => '#FF4D4F', // red
                    'backgroundColor' => 'rgba(255, 77, 79, 0.14)',
                    'fill'            => true,
                    'tension'         => 0.45,
                    'borderWidth'     => 2,
                ],
                [
                    'label'           => 'Motor Amps (A)',
                    'data'            => $records->pluck('motor_amps'),
                    'borderColor'     => '#005CB4', // dark blue
                    'backgroundColor' => 'rgba(0, 92, 180, 0.14)',
                    'fill'            => true,
                    'tension'         => 0.45,
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $records
                ->pluck('created_at')
                ->map(fn ($date) => $date->format('d M H:i')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        $records = $this->getRecords();

        // Gather all values to set an auto range with padding
        $values = collect()
            ->merge($records->pluck('sat_evap_t'))
            ->merge($records->pluck('conds_p'))
            ->merge($records->pluck('motor_amps'))
            ->filter(fn ($v) => $v !== null);

        $min = $values->min();
        $max = $values->max();

        // Add breathing room
        $padding = ($max !== null && $min !== null) ? max(1, ($max - $min) * 0.1) : 5;
        $min = $min !== null ? floor($min - $padding) : 0;
        $max = $max !== null ? ceil($max + $padding) : 10;

        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'min' => $min,
                    'max' => $max,
                    'ticks' => [
                        'font' => ['size' => 11],
                        'color' => '#475569',
                    ],
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                        'lineWidth' => 1,
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'font' => ['size' => 10],
                        'color' => '#475569',
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => [
                        'font' => ['size' => 11],
                    ],
                ],
                [
                    'id' => 'bmsBackground',
                    'beforeDraw' => RawJs::make(<<<'JS'
                        function (chart, args, opts) {
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return;
                            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, opts?.top || 'rgba(0, 95, 180, 0.06)');
                            gradient.addColorStop(1, opts?.bottom || 'rgba(0, 159, 218, 0.02)');
                            ctx.save();
                            ctx.fillStyle = gradient;
                            ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                            ctx.restore();
                        }
                    JS),
                    'top' => 'rgba(0, 95, 180, 0.06)',
                    'bottom' => 'rgba(0, 159, 218, 0.02)',
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                    'hitRadius' => 8,
                    'hoverRadius' => 4,
                ],
            ],
            'layout' => [
                'padding' => [
                    'top' => 12,
                    'right' => 16,
                    'bottom' => 8,
                    'left' => 8,
                ],
            ],
        ];
    }
}
