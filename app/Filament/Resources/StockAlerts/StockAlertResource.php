<?php

namespace App\Filament\Resources\StockAlerts;

use App\Filament\Resources\StockAlerts\Pages\ListStockAlerts;
use App\Filament\Resources\StockAlerts\Tables\StockAlertsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\StockAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockAlertResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = StockAlert::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Stock Alerts';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::canAccessInventory();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory Management';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_resolved', false)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('is_resolved', false)->count();
        return $count > 0 ? 'danger' : 'success';
    }

    public static function table(Table $table): Table
    {
        return StockAlertsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAlerts::route('/'),
        ];
    }
}
