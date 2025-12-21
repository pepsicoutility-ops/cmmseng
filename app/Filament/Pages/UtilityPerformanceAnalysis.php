<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UtilityPerformanceAnalysis extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

   //rotected string $view = 'filament.pages.utility-performance-analysis';
    
    protected static ?string $navigationLabel = 'Performance Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    // Auto refresh every 30 seconds
    protected static ?string $pollingInterval = '30s';
    
    protected static ?string $title = 'Utility Performance Analysis';

    public static function getNavigationGroup(): ?string
    {
        return 'Utility Performance';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'super_admin' ||
            $user->department === 'utility'
        );
    }

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        
        // Only show widgets for super_admin or utility department
        if (!$user || (!($user->role === 'super_admin' || $user->department === 'utility'))) {
            return [];
        }
        
        return [
            // AI Prediction & Insights Section (NEW)
            \App\Filament\Widgets\AiPredictionStatsWidget::class,
            \App\Filament\Widgets\AiInsightsTableWidget::class,
            
            // Chiller 1 Section
            \App\Filament\Widgets\Chiller1StatsWidget::class,
            \App\Filament\Widgets\Chiller1TableWidget::class,
            \App\Filament\Widgets\Chiller1Trend::class,
            
            // Chiller 2 Section
            \App\Filament\Widgets\Chiller2StatsWidget::class,
            \App\Filament\Widgets\Chiller2TableWidget::class,
            \App\Filament\Widgets\Chiller2Trend::class, 
            
            // Compressor 1 Section
            \App\Filament\Widgets\Compressor1StatsWidget::class,
            \App\Filament\Widgets\Compressor1TableWidget::class,
            
            // Compressor 2 Section
            \App\Filament\Widgets\Compressor2StatsWidget::class,
            \App\Filament\Widgets\Compressor2TableWidget::class,
            
            // AHU Section
            \App\Filament\Widgets\AhuStatsWidget::class,
            \App\Filament\Widgets\AhuTableWidget::class,
        ];
    }
}
