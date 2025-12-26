<?php

namespace App\Filament\Resources\Compressor1Checklists;

use App\Filament\Resources\Compressor1Checklists\Pages\CreateCompressor1Checklist;
use App\Filament\Resources\Compressor1Checklists\Pages\EditCompressor1Checklist;
use App\Filament\Resources\Compressor1Checklists\Pages\ListCompressor1Checklists;
use App\Filament\Resources\Compressor1Checklists\Schemas\Compressor1ChecklistForm;
use App\Filament\Resources\Compressor1Checklists\Tables\Compressor1ChecklistsTable;
use App\Models\Compressor1Checklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class Compressor1ChecklistResource extends Resource
{
    protected static ?string $model = Compressor1Checklist::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCpuChip;
    
    protected static ?string $navigationLabel = 'Compressor 1';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Compressor 1 Checklist';
    
    protected static ?string $pluralModelLabel = 'Compressor 1 Checklists';

    public static function form(Schema $schema): Schema
    {
        return Compressor1ChecklistForm::configure($schema);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Checklists';
    }

    public static function table(Table $table): Table
    {
        return Compressor1ChecklistsTable::configure($table);
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
            'index' => ListCompressor1Checklists::route('/'),
            'create' => CreateCompressor1Checklist::route('/create'),
            'edit' => EditCompressor1Checklist::route('/{record}/edit'),
        ];
    }
}
