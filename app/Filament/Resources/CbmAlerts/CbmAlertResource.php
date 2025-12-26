<?php

namespace App\Filament\Resources\CbmAlerts;

use App\Filament\Resources\CbmAlerts\Pages;
use App\Filament\Resources\CbmAlerts\Tables\CbmAlertsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\CbmAlert;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CbmAlertResource extends Resource
{
    use HasRoleBasedAccess;

    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }
    protected static ?string $model = CbmAlert::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string | UnitEnum | null $navigationGroup = 'CBM & Utility';

    protected static ?string $navigationLabel = 'CBM Alerts';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'alert_no';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('severity', 'critical')->where('status', 'open')->exists() 
            ? 'danger' 
            : 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Alert Information')
                ->columns(2)
                ->components([
                    TextInput::make('alert_no')
                        ->label('Alert No')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('checklist_type')
                        ->label('Checklist Type')
                        ->disabled(),

                    TextInput::make('parameter_name')
                        ->label('Parameter')
                        ->disabled(),

                    TextInput::make('recorded_value')
                        ->label('Recorded Value')
                        ->disabled(),

                    TextInput::make('threshold_value')
                        ->label('Threshold Value')
                        ->disabled(),

                    TextInput::make('alert_type')
                        ->label('Alert Type')
                        ->disabled(),

                    TextInput::make('severity')
                        ->label('Severity')
                        ->disabled(),

                    TextInput::make('status')
                        ->label('Status')
                        ->disabled(),
                ]),

            Section::make('Resolution')
                ->components([
                    Textarea::make('resolution_notes')
                        ->label('Resolution Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CbmAlertsTable::make($table);
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
            'index' => Pages\ListCbmAlerts::route('/'),
            'view' => Pages\ViewCbmAlert::route('/{record}'),
        ];
    }
}
