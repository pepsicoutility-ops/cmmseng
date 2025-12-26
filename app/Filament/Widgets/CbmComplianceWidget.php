<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\CbmComplianceService;
use App\Models\CbmAlert;
use Illuminate\Support\Facades\Auth;

class CbmComplianceWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $service = app(CbmComplianceService::class);
        $summary = $service->getComplianceSummary();

        $monthlyColor = $summary['monthly']['compliance'] >= 90 ? 'success' : 
            ($summary['monthly']['compliance'] >= 80 ? 'warning' : 'danger');

        $weeklyColor = $summary['weekly']['compliance'] >= 90 ? 'success' : 
            ($summary['weekly']['compliance'] >= 80 ? 'warning' : 'danger');

        return [
            Stat::make('CBM Monthly Compliance', $summary['monthly']['compliance'] . '%')
                ->description("Target: ≥90% | {$summary['monthly']['executed']}/{$summary['monthly']['scheduled']} executed")
                ->descriptionIcon($monthlyColor === 'success' ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($monthlyColor)
                ->chart([75, 82, 88, 90, 92, 88, $summary['monthly']['compliance']]),

            Stat::make('CBM Weekly Compliance', $summary['weekly']['compliance'] . '%')
                ->description("{$summary['weekly']['executed']}/{$summary['weekly']['scheduled']} executed this week")
                ->color($weeklyColor),

            Stat::make("Today's CBM", "{$summary['today']['executed']}/{$summary['today']['scheduled']}")
                ->description("{$summary['today']['pending']} pending checks")
                ->descriptionIcon($summary['today']['pending'] > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($summary['today']['pending'] > 0 ? 'warning' : 'success'),

            Stat::make('Open Alerts', $summary['alerts']['open'])
                ->description($summary['alerts']['critical'] > 0 ? "{$summary['alerts']['critical']} critical" : 'No critical alerts')
                ->descriptionIcon($summary['alerts']['critical'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($summary['alerts']['critical'] > 0 ? 'danger' : ($summary['alerts']['open'] > 0 ? 'warning' : 'success')),
        ];
    }

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }
}
