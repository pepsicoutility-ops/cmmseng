<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\UtilityCostService;
use Illuminate\Support\Facades\Auth;

class UtilityCostWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $service = app(UtilityCostService::class);
        $summary = $service->getUtilitySummary();

        $waterColor = match($summary['water']['status'] ?? 'on_target') {
            'exceeded' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };

        $electricityColor = match($summary['electricity']['status'] ?? 'on_target') {
            'exceeded' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };

        $gasColor = match($summary['gas']['status'] ?? 'on_target') {
            'exceeded' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };

        $waterTarget = $summary['water']['target'] ? " / Target: {$summary['water']['target']}" : '';
        $electricityTarget = $summary['electricity']['target'] ? " / Target: {$summary['electricity']['target']}" : '';
        $gasTarget = $summary['gas']['target'] ? " / Target: {$summary['gas']['target']}" : '';

        return [
            Stat::make('Production This Month', number_format($summary['production']['this_month'], 0) . ' kg')
                ->description('Total approved production')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Water per kg', $summary['water']['per_kg'] . ' L/kg')
                ->description("Consumption: " . number_format($summary['water']['consumption'], 0) . " L{$waterTarget}")
                ->descriptionIcon($waterColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($waterColor),

            Stat::make('Electricity per kg', $summary['electricity']['per_kg'] . ' kWh/kg')
                ->description("Consumption: " . number_format($summary['electricity']['consumption'], 0) . " kWh{$electricityTarget}")
                ->descriptionIcon($electricityColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($electricityColor),

            Stat::make('Gas per kg', $summary['gas']['per_kg'] . ' kWh/kg')
                ->description("Consumption: " . number_format($summary['gas']['consumption'], 0) . " kWh{$gasTarget}")
                ->descriptionIcon($gasColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($gasColor),
        ];
    }

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }
}
