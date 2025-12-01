<?php

namespace App\Filament\Widgets;

use App\Models\PmCompliance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PmComplianceChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
    
    public function getHeading(): ?string
    {
        return 'PM Compliance Trend (4 Weeks)';
    }
    
    protected function getData(): array
    {
        // Get last 4 weeks compliance data
        $data = PmCompliance::query()
            ->where('period', 'week')
            ->orderBy('period_end', 'desc')
            ->limit(4)
            ->get()
            ->reverse();
        
        $labels = [];
        $complianceValues = [];
        
        foreach ($data as $record) {
            $labels[] = $record->period_start->format('M d') . ' - ' . $record->period_end->format('M d');
            $complianceValues[] = round($record->compliance_percentage, 1);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Compliance %',
                    'data' => $complianceValues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
