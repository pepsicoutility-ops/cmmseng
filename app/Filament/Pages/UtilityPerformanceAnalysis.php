<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AhuTableWidget;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UtilityPerformanceAnalysis extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

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
        // Operators can only access Work Orders
        if ($user && $user->role === 'operator') {
            return false;
        }
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
            // AHU Section - Only AHU Table Widget
            AhuTableWidget::class,
        ];
    }
}
