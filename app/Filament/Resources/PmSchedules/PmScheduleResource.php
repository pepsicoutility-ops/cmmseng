<?php

namespace App\Filament\Resources\PmSchedules;

use App\Filament\Resources\PmSchedules\Pages\CreatePmSchedule;
use App\Filament\Resources\PmSchedules\Pages\EditPmSchedule;
use App\Filament\Resources\PmSchedules\Pages\ListPmSchedules;
use App\Filament\Resources\PmSchedules\Pages\ViewPmSchedule;
use App\Filament\Resources\PmSchedules\Schemas\PmScheduleForm;
use App\Filament\Resources\PmSchedules\Schemas\PmScheduleInfolist;
use App\Filament\Resources\PmSchedules\Tables\PmSchedulesTable;
use App\Models\PmSchedule;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PmScheduleResource extends Resource
{
    protected static ?string $model = PmSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
    
    protected static ?string $navigationLabel = 'PM Schedules';
    
    protected static ?int $navigationSort = 1;
    
    /**
     * Personalized query based on user role
     * - Technician: See ONLY their own PM schedules
     * - Asisten Manager: See PM schedules in their department
     * - Manager/Super Admin: See all PM schedules
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['asset', 'subAsset', 'assignedTo']);
        $user = Auth::user();
        
        return match($user->role) {
            'technician' => $query->where('assigned_to_gpid', $user->gpid),
            'asisten_manager' => $query->where('department', $user->department),
            default => $query,
        };
    }
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'PM Management';
    }

    public static function form(Form $form): Form
    {
        return PmScheduleForm::configure($form);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return PmScheduleInfolist::configure($infolist);
    }

    public static function table(Table $table): Table
    {
        return PmSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PmSchedules\RelationManagers\PmChecklistItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPmSchedules::route('/'),
            'create' => CreatePmSchedule::route('/create'),
            'view' => ViewPmSchedule::route('/{record}'),
            'edit' => EditPmSchedule::route('/{record}/edit'),
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
