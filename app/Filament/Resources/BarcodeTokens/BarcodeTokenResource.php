<?php

namespace App\Filament\Resources\BarcodeTokens;

use App\Filament\Resources\BarcodeTokens\Pages\ListBarcodeTokens;
use App\Filament\Resources\BarcodeTokens\Pages\CreateBarcodeToken;
use App\Filament\Resources\BarcodeTokens\Pages\EditBarcodeToken;
use App\Filament\Resources\BarcodeTokens\Schemas\BarcodeTokenForm;
use App\Filament\Resources\BarcodeTokens\Tables\BarcodeTokensTable;
use App\Models\BarcodeToken;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BarcodeTokenResource extends Resource
{
    protected static ?string $model = BarcodeToken::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Barcode Tokens';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System Management';
    }

    public static function form(Schema $schema): Schema
    {
        return BarcodeTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarcodeTokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarcodeTokens::route('/'),
            'create' => CreateBarcodeToken::route('/create'),
            'edit' => EditBarcodeToken::route('/{record}/edit'),
        ];
    }
}
