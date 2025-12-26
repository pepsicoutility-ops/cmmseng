<?php

namespace App\Filament\Resources\EquipmentTroubles;

use App\Filament\Resources\EquipmentTroubles\Pages\CreateEquipmentTrouble;
use App\Filament\Resources\EquipmentTroubles\Pages\EditEquipmentTrouble;
use App\Filament\Resources\EquipmentTroubles\Pages\ListEquipmentTroubles;
use App\Filament\Resources\EquipmentTroubles\Pages\ViewEquipmentTrouble;
use App\Filament\Resources\EquipmentTroubles\Schemas\EquipmentTroubleForm;
use App\Filament\Resources\EquipmentTroubles\Schemas\EquipmentTroubleInfolist;
use App\Filament\Resources\EquipmentTroubles\Tables\EquipmentTroublesTable;
use App\Models\EquipmentTrouble;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Traits\HasRoleBasedAccess;
use Illuminate\Database\Eloquent\Builder;

class EquipmentTroubleResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = EquipmentTrouble::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    
    protected static ?string $navigationLabel = 'Equipment Troubles';
    
    protected static ?string $modelLabel = 'Equipment Trouble';
    
    protected static ?string $pluralModelLabel = 'Equipment Troubles';
    
    protected static ?int $navigationSort = 5;

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Maintenance';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::open()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::open()->count();
        if ($count > 0) {
            return 'danger';
        }
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return EquipmentTroubleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentTroubleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentTroublesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Semua role bisa lihat semua equipment trouble
        // Eager load technicians relationship untuk policy check
        return parent::getEloquentQuery()->with('technicians');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipmentTroubles::route('/'),
            'create' => CreateEquipmentTrouble::route('/create'),
            'view' => ViewEquipmentTrouble::route('/{record}'),
            'edit' => EditEquipmentTrouble::route('/{record}/edit'),
        ];
    }
}
