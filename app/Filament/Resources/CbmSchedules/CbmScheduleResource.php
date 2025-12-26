<?php

namespace App\Filament\Resources\CbmSchedules;

use App\Filament\Resources\CbmSchedules\Pages;
use App\Filament\Resources\CbmSchedules\Tables\CbmSchedulesTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\CbmSchedule;
use App\Models\Area;
use App\Models\Asset;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CbmScheduleResource extends Resource
{
    use HasRoleBasedAccess;

    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }
    protected static ?string $model = CbmSchedule::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string | UnitEnum | null $navigationGroup = 'CBM & Utility';

    protected static ?string $navigationLabel = 'CBM Schedules';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'schedule_no';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule Information')
                ->columns(2)
                ->components([
                    TextInput::make('schedule_no')
                        ->label('Schedule No')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated'),

                    Select::make('checklist_type')
                        ->label('Checklist Type')
                        ->options([
                            'compressor1' => 'Compressor 1',
                            'compressor2' => 'Compressor 2',
                            'chiller1' => 'Chiller 1',
                            'chiller2' => 'Chiller 2',
                            'ahu' => 'AHU',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('area_id')
                        ->label('Area')
                        ->options(Area::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->native(false),

                    Select::make('asset_id')
                        ->label('Asset (Optional)')
                        ->options(fn (Get $get) => 
                            Asset::where('area_id', $get('area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false),

                    Select::make('frequency')
                        ->label('Frequency')
                        ->options([
                            'per_shift' => 'Per Shift',
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->default('per_shift')
                        ->required()
                        ->native(false)
                        ->live(),

                    TextInput::make('shifts_per_day')
                        ->label('Shifts Per Day')
                        ->numeric()
                        ->default(3)
                        ->minValue(1)
                        ->maxValue(4)
                        ->visible(fn (Get $get) => $get('frequency') === 'per_shift'),

                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->default(now())
                        ->native(false),

                    DatePicker::make('end_date')
                        ->label('End Date (Optional)')
                        ->native(false)
                        ->afterOrEqual('start_date'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
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
        return CbmSchedulesTable::make($table);
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
            'index' => Pages\ListCbmSchedules::route('/'),
            'create' => Pages\CreateCbmSchedule::route('/create'),
            'edit' => Pages\EditCbmSchedule::route('/{record}/edit'),
            'view' => Pages\ViewCbmSchedule::route('/{record}'),
        ];
    }
}
