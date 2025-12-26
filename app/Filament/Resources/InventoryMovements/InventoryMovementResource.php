<?php

namespace App\Filament\Resources\InventoryMovements;

use App\Filament\Resources\InventoryMovements\Pages\ListInventoryMovements;
use App\Filament\Resources\InventoryMovements\Tables\InventoryMovementsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\InventoryMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = InventoryMovement::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Inventory Movements';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return static::canAccessInventory();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory Management';
    }

    public static function table(Table $table): Table
    {
        return InventoryMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryMovements::route('/'),
        ];
    }
}
