<?php

namespace App\Filament\Resources\PmSchedules;

use App\Filament\Resources\PmSchedules\RelationManagers\PmChecklistItemsRelationManager;
use App\Filament\Resources\PmSchedules\Pages\CreatePmSchedule;
use App\Filament\Resources\PmSchedules\Pages\EditPmSchedule;
use App\Filament\Resources\PmSchedules\Pages\ListPmSchedules;
use App\Filament\Resources\PmSchedules\Pages\ViewPmSchedule;
use App\Filament\Resources\PmSchedules\Schemas\PmScheduleForm;
use App\Filament\Resources\PmSchedules\Schemas\PmScheduleInfolist;
use App\Filament\Resources\PmSchedules\Tables\PmSchedulesTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\PmSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PmScheduleResource extends Resource
{
    use HasRoleBasedAccess;
    protected static ?string $model = PmSchedule::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendar;
    
    protected static ?string $navigationLabel = 'PM Schedules';
    
    protected static ?int $navigationSort = 1;
    
    /**
     * Personalized query based on user role
     * - Technician: See ONLY their own PM schedules (excluding those with in-progress execution)
     * - Asisten Manager: See PM schedules in their department
     * - Manager/Super Admin: See all PM schedules
     * 
     * FILTERING BY FREQUENCY:
     * For weekly schedules, only show PM when current week matches the frequency interval
     * Example: frequency=4 means PM appears every 4 weeks (week 4, 8, 12, 16, etc)
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['asset', 'subAsset', 'assignedTo']);
        $user = Auth::user();
        
        // Get current week number (1-52)
        $currentWeek = (int) now()->format('W');
        
        // Filter weekly schedules by frequency
        // Only show PM if current_week is divisible by frequency OR if it's not a weekly schedule
        $query->where(function($q) use ($currentWeek) {
            $q->where('schedule_type', '!=', 'weekly')  // Show all non-weekly schedules
              ->orWhereRaw("? % frequency = 0", [$currentWeek]);  // Show weekly if week matches frequency
        });
        
        return match($user->role) {
            'technician' => $query->where('assigned_to_gpid', $user->gpid)
                ->whereDoesntHave('pmExecutions', function ($q) {
                    $q->where('status', 'in_progress')
                      ->orWhere(function ($subQ) {
                          $subQ->whereDate('created_at', today())
                               ->whereIn('status', ['in_progress', 'completed']);
                      });
                }),
            'asisten_manager' => $query->where('department', $user->department),
            default => $query,
        };
    }
    
    public static function canAccess(): bool
    {
        return static::canAccessManagementAndTechnician();
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'PM Management';
    }

    public static function form(Schema $schema): Schema
    {
        return PmScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PmScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PmSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PmChecklistItemsRelationManager::class,
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
