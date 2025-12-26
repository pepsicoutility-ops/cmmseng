<?php

namespace App\Filament\Pages;

use App\Services\SystemMonitorService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class FilaforgeSystemMonitor extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $navigationLabel = 'Filaforge System Monitor';

    protected static ?string $title = 'Filaforge System Monitor';

    protected static ?int $navigationSort = 200;

    protected string $view = 'filament.pages.filaforge-system-monitor';

    public array $system = [];

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $this->system = app(SystemMonitorService::class)->snapshot();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
}
