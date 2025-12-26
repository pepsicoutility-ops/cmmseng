<?php

namespace App\Filament\Resources\UtilityConsumptions;

use App\Filament\Resources\UtilityConsumptions\Pages;
use App\Filament\Resources\UtilityConsumptions\Tables\UtilityConsumptionsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\UtilityConsumption;
use App\Models\Area;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UtilityConsumptionResource extends Resource
{
    use HasRoleBasedAccess;

    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }
    protected static ?string $model = UtilityConsumption::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string | UnitEnum | null $navigationGroup = 'CBM & Utility';

    protected static ?string $navigationLabel = 'Utility Consumptions';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'record_no';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Record Information')
                ->columns(2)
                ->components([
                    TextInput::make('record_no')
                        ->label('Record No')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated'),

                    DatePicker::make('consumption_date')
                        ->label('Date')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Select::make('shift')
                        ->label('Shift')
                        ->options([
                            1 => 'Shift 1',
                            2 => 'Shift 2',
                            3 => 'Shift 3',
                        ])
                        ->placeholder('Daily Total (All Shifts)')
                        ->native(false),

                    Select::make('area_id')
                        ->label('Area (Optional)')
                        ->options(Area::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->placeholder('Plant-wide'),
                ]),

            Section::make('Water Consumption')
                ->icon(Heroicon::OutlinedBeaker)
                ->columns(3)
                ->components([
                    TextInput::make('water_meter_start')
                        ->label('Meter Start')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('L'),

                    TextInput::make('water_meter_end')
                        ->label('Meter End')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('L'),

                    TextInput::make('water_consumption')
                        ->label('Consumption')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('L')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from meter readings'),
                ]),

            Section::make('Electricity Consumption')
                ->icon(Heroicon::OutlinedBolt)
                ->columns(3)
                ->components([
                    TextInput::make('electricity_meter_start')
                        ->label('Meter Start')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh'),

                    TextInput::make('electricity_meter_end')
                        ->label('Meter End')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh'),

                    TextInput::make('electricity_consumption')
                        ->label('Consumption')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from meter readings'),
                ]),

            Section::make('Gas Consumption')
                ->icon(Heroicon::OutlinedFire)
                ->columns(3)
                ->components([
                    TextInput::make('gas_meter_start')
                        ->label('Meter Start')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh'),

                    TextInput::make('gas_meter_end')
                        ->label('Meter End')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh'),

                    TextInput::make('gas_consumption')
                        ->label('Consumption')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('kWh')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from meter readings'),
                ]),

            Section::make('Notes')
                ->collapsible()
                ->components([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return UtilityConsumptionsTable::make($table);
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
            'index' => Pages\ListUtilityConsumptions::route('/'),
            'create' => Pages\CreateUtilityConsumption::route('/create'),
            'edit' => Pages\EditUtilityConsumption::route('/{record}/edit'),
            'view' => Pages\ViewUtilityConsumption::route('/{record}'),
        ];
    }
}
