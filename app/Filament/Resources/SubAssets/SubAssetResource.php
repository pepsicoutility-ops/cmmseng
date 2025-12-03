<?php

namespace App\Filament\Resources\SubAssets;

use App\Filament\Resources\SubAssets\Pages\CreateSubAsset;
use App\Filament\Resources\SubAssets\Pages\EditSubAsset;
use App\Filament\Resources\SubAssets\Pages\ListSubAssets;
use App\Filament\Resources\SubAssets\Pages\ViewSubAsset;
use App\Filament\Resources\SubAssets\Schemas\SubAssetForm;
use App\Filament\Resources\SubAssets\Schemas\SubAssetInfolist;
use App\Filament\Resources\SubAssets\Tables\SubAssetsTable;
use App\Models\SubAsset;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SubAssetResource extends Resource
{
    protected static ?string $model = SubAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCubeTransparent;
    
    protected static ?string $navigationLabel = 'Sub Assets';
    
    protected static ?int $navigationSort = 4;
    
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
        return SubAssetForm::configure($form);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return SubAssetInfolist::configure($infolist);
    }

    public static function table(Table $table): Table
    {
        return SubAssetsTable::configure($table);
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
            'index' => ListSubAssets::route('/'),
            'create' => CreateSubAsset::route('/create'),
            'view' => ViewSubAsset::route('/{record}'),
            'edit' => EditSubAsset::route('/{record}/edit'),
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
