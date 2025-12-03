<?php

namespace App\Filament\Resources\WorkOrders;

use App\Filament\Resources\WorkOrders\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Filament\Resources\WorkOrders\Schemas\WorkOrderForm;
use App\Filament\Resources\WorkOrders\Schemas\WorkOrderInfolist;
use App\Filament\Resources\WorkOrders\Tables\WorkOrdersTable;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench';
    
    protected static ?string $navigationLabel = 'Work Orders';
    
    protected static ?int $navigationSort = 1;
    
    /**
     * Personalized query based on user role
     * - Technician: See WO assigned to their department
     * - Asisten Manager: See WO in their department
     * - Manager/Super Admin: See all WO
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['asset']);
        $user = Auth::user();
        
        return match($user->role) {
            'technician', 'asisten_manager' => $query->where('assign_to', $user->department),
            default => $query,  // operators, super_admin, manager see all WOs
        };
    }
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician', 'operator']);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Work Order Management';
    }

    public static function form(Form $form): Form
    {
        return WorkOrderForm::configure($form);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return WorkOrderInfolist::configure($infolist);
    }

    public static function table(Table $table): Table
    {
        return WorkOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WorkOrderResource\RelationManagers\WoProcessesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkOrders::route('/'),
            'create' => CreateWorkOrder::route('/create'),
            'view' => ViewWorkOrder::route('/{record}'),
            'edit' => EditWorkOrder::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
