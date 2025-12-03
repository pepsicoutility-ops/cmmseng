<?php

namespace App\Filament\Resources\SubAreas;

use App\Filament\Resources\SubAreas\Pages\CreateSubArea;
use App\Filament\Resources\SubAreas\Pages\EditSubArea;
use App\Filament\Resources\SubAreas\Pages\ListSubAreas;
use App\Filament\Resources\SubAreas\Pages\ViewSubArea;
use App\Filament\Resources\SubAreas\Schemas\SubAreaForm;
use App\Filament\Resources\SubAreas\Schemas\SubAreaInfolist;
use App\Filament\Resources\SubAreas\Tables\SubAreasTable;
use App\Models\SubArea;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SubAreaResource extends Resource
{
    protected static ?string $model = SubArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $navigationLabel = 'Sub Areas';
    
    protected static ?int $navigationSort = 2;
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function form(Form $form): Form
    {
        return SubAreaForm::configure($form);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return SubAreaInfolist::configure($infolist);
    }

    public static function table(Table $table): Table
    {
        return SubAreasTable::configure($table);
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
            'index' => ListSubAreas::route('/'),
            'create' => CreateSubArea::route('/create'),
            'view' => ViewSubArea::route('/{record}'),
            'edit' => EditSubArea::route('/{record}/edit'),
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
