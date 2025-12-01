<?php

namespace App\Filament\Resources\Chiller2Checklists\Chiller2Checklists;

use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Pages\CreateChiller2Checklist;
use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Pages\EditChiller2Checklist;
use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Pages\ListChiller2Checklists;
use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Schemas\Chiller2ChecklistForm;
use App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Tables\Chiller2ChecklistsTable;
use App\Models\Chiller2Checklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class Chiller2ChecklistResource extends Resource
{
    protected static ?string $model = Chiller2Checklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;
    
    protected static ?string $navigationLabel = 'Chiller 2';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Chiller 2 Checklist';
    
    protected static ?string $pluralModelLabel = 'Chiller 2 Checklists';

    public static function form(Schema $schema): Schema
    {
        return Chiller2ChecklistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Chiller2ChecklistsTable::configure($table);
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
            'index' => ListChiller2Checklists::route('/'),
            'create' => CreateChiller2Checklist::route('/create'),
            'edit' => EditChiller2Checklist::route('/{record}/edit'),
        ];
    }
}
