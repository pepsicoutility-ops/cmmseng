<?php

namespace App\Filament\Resources\Chiller1Checklists\Chiller1Checklists;

use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages\CreateChiller1Checklist;
use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages\EditChiller1Checklist;
use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Pages\ListChiller1Checklists;
use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Schemas\Chiller1ChecklistForm;
use App\Filament\Resources\Chiller1Checklists\Chiller1Checklists\Tables\Chiller1ChecklistsTable;
use App\Models\Chiller1Checklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class Chiller1ChecklistResource extends Resource
{
    protected static ?string $model = Chiller1Checklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;
    
    protected static ?string $navigationLabel = 'Chiller 1';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $modelLabel = 'Chiller 1 Checklist';
    
    protected static ?string $pluralModelLabel = 'Chiller 1 Checklists';

    public static function form(Schema $schema): Schema
    {
        return Chiller1ChecklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Chiller1ChecklistsTable::configure($table);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Master Checklists';
    }
    
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChiller1Checklists::route('/'),
            'create' => CreateChiller1Checklist::route('/create'),
            'edit' => EditChiller1Checklist::route('/{record}/edit'),
        ];
    }
}
