<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    // Poll every 3 seconds for real-time updates
    protected static ?string $pollingInterval = '3s';
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        // Operators should not see the dashboard
        return $user && $user->role !== 'operator';
    }
    
    /**
     * Get widgets for the dashboard
     * Only show main CMMS widgets, NOT utility-specific widgets
     */
    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();
        
        // Filter out utility-specific widgets
        $utilityWidgets = [
            \App\Filament\Widgets\Chiller1StatsWidget::class,
            \App\Filament\Widgets\Chiller1TableWidget::class,
            \App\Filament\Widgets\Chiller1Trend::class,
            \App\Filament\Widgets\Chiller2StatsWidget::class,
            \App\Filament\Widgets\Chiller2TableWidget::class,
            \App\Filament\Widgets\Compressor1StatsWidget::class,
            \App\Filament\Widgets\Compressor1TableWidget::class,
            \App\Filament\Widgets\Compressor2StatsWidget::class,
            \App\Filament\Widgets\Compressor2TableWidget::class,
            \App\Filament\Widgets\AhuStatsWidget::class,
            \App\Filament\Widgets\AhuTableWidget::class,
            \App\Filament\Widgets\AiPredictionStatsWidget::class,
            \App\Filament\Widgets\AiInsightsTableWidget::class,
            \App\Filament\Widgets\UtilityPerformanceWidget::class,
            \App\Filament\Widgets\MasterChecklistsWidget::class,
            \App\Filament\Widgets\EnergyMetricsChartWidget::class,
        ];
        
        return array_filter($widgets, function($widget) use ($utilityWidgets) {
            return !in_array($widget, $utilityWidgets);
        });
    }
}
