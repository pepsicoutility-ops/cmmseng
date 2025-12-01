<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists;

use App\Filament\Resources\AhuChecklists\AhuChecklists\Pages\CreateAhuChecklist;
use App\Filament\Resources\AhuChecklists\AhuChecklists\Pages\EditAhuChecklist;
use App\Filament\Resources\AhuChecklists\AhuChecklists\Pages\ListAhuChecklists;
use App\Filament\Resources\AhuChecklists\AhuChecklists\Schemas\AhuChecklistForm;
use App\Filament\Resources\AhuChecklists\AhuChecklists\Tables\AhuChecklistsTable;
use App\Models\AhuChecklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AhuChecklistResource extends Resource
{
    protected static ?string $model = AhuChecklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    
    protected static ?string $navigationLabel = 'AHU';
    
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'super_admin' ||
            $user->department === 'utility'
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return AhuChecklistForm::configure($schema);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Checklists';
    }

    public static function table(Table $table): Table
    {
        return AhuChecklistsTable::configure($table);
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
            'index' => ListAhuChecklists::route('/'),
            'create' => CreateAhuChecklist::route('/create'),
            'edit' => EditAhuChecklist::route('/{record}/edit'),
        ];
    }
}
