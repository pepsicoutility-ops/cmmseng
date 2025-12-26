<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasRoleBasedAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use HasRoleBasedAccess;
    
    // Poll every 3 seconds for real-time updates
    protected static ?string $pollingInterval = '3s';
    
    public static function canAccess(): bool
    {
        // Operators should not see the dashboard
        return static::canAccessExcludeOperator();
    }
}
