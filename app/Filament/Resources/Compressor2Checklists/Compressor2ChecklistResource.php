<?php

namespace App\Filament\Resources\Compressor2Checklists;

use App\Filament\Resources\Compressor2Checklists\Pages\CreateCompressor2Checklist;
use App\Filament\Resources\Compressor2Checklists\Pages\EditCompressor2Checklist;
use App\Filament\Resources\Compressor2Checklists\Pages\ListCompressor2Checklists;
use App\Filament\Resources\Compressor2Checklists\Schemas\Compressor2ChecklistForm;
use App\Filament\Resources\Compressor2Checklists\Tables\Compressor2ChecklistsTable;
use App\Models\Compressor2Checklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class Compressor2ChecklistResource extends Resource
{
    protected static ?string $model = Compressor2Checklist::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCpuChip;
    
    protected static ?string $navigationLabel = 'Compressor 2';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Compressor 2 Checklist';
    
    protected static ?string $pluralModelLabel = 'Compressor 2 Checklists';

    public static function form(Schema $schema): Schema
    {
        return Compressor2ChecklistForm::configure($schema);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Checklists';
    }

    public static function table(Table $table): Table
    {
        return Compressor2ChecklistsTable::configure($table);
    }
    
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        // Operators can only access Work Orders
        if ($user && $user->role === 'operator') {
            return false;
        }
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
            'index' => ListCompressor2Checklists::route('/'),
            'create' => CreateCompressor2Checklist::route('/create'),
            'edit' => EditCompressor2Checklist::route('/{record}/edit'),
        ];
    }
}
