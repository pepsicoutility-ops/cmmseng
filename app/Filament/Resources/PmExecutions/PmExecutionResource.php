<?php

namespace App\Filament\Resources\PmExecutions;

use App\Filament\Resources\PmExecutions\Pages\CreatePmExecution;
use App\Filament\Resources\PmExecutions\Pages\EditPmExecution;
use App\Filament\Resources\PmExecutions\Pages\ListPmExecutions;
use App\Filament\Resources\PmExecutions\Pages\ViewPmExecution;
use App\Filament\Resources\PmExecutions\Schemas\PmExecutionForm;
use App\Filament\Resources\PmExecutions\Schemas\PmExecutionInfolist;
use App\Filament\Resources\PmExecutions\Tables\PmExecutionsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\PmExecution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PmExecutionResource extends Resource
{
    use HasRoleBasedAccess;
    protected static ?string $model = PmExecution::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    
    protected static ?string $navigationLabel = 'PM Executions';
    
    protected static ?int $navigationSort = 2;
    
    /**
     * Personalized query based on user role
     * - Technician: See ONLY their own PM executions
     * - Asisten Manager: See PM executions in their department
     * - Manager/Super Admin: See all PM executions
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pmSchedule', 'executedBy']);
        $user = Auth::user();
        
        return match($user->role) {
            'technician' => $query->where('executed_by_gpid', $user->gpid),
            'asisten_manager' => $query->whereHas('pmSchedule', function ($q) use ($user) {
                $q->where('department', $user->department);
            }),
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
        return PmExecutionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PmExecutionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PmExecutionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPmExecutions::route('/'),
            'create' => CreatePmExecution::route('/create'),
            'view' => ViewPmExecution::route('/{record}'),
            'edit' => EditPmExecution::route('/{record}/edit'),
        ];
    }
}
