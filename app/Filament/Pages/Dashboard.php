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
}
