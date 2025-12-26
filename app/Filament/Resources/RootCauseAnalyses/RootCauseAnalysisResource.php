<?php

namespace App\Filament\Resources\RootCauseAnalyses;

use App\Filament\Resources\RootCauseAnalyses\Pages;
use App\Filament\Resources\RootCauseAnalyses\Tables\RootCauseAnalysesTable;
use App\Filament\Resources\RootCauseAnalyses\Schemas\RootCauseAnalysisForm;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\RootCauseAnalysis;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RootCauseAnalysisResource extends Resource
{
    use HasRoleBasedAccess;

    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }
    protected static ?string $model = RootCauseAnalysis::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static string | UnitEnum | null $navigationGroup = 'Maintenance';

    protected static ?string $navigationLabel = 'Root Cause Analysis';

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'root-cause-analyses';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::pending()->count();
        return $count > 0 ? 'warning' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return RootCauseAnalysisForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return RootCauseAnalysesTable::make($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRootCauseAnalyses::route('/'),
            'create' => Pages\CreateRootCauseAnalysis::route('/create'),
            'view' => Pages\ViewRootCauseAnalysis::route('/{record}'),
            'edit' => Pages\EditRootCauseAnalysis::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = Auth::user();

        // Technicians see only their own RCAs
        if ($user->role === 'technician') {
            $query->where('created_by_gpid', $user->gpid);
        }
        // Asisten Manager sees department RCAs
        elseif ($user->role === 'asisten_manager') {
            $query->whereHas('workOrder', function ($q) use ($user) {
                $q->where('assign_to', $user->department);
            });
        }

        return $query;
    }
}
