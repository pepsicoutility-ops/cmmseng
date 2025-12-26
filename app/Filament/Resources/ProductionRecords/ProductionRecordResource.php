<?php

namespace App\Filament\Resources\ProductionRecords;

use App\Filament\Resources\ProductionRecords\Pages;
use App\Filament\Resources\ProductionRecords\Tables\ProductionRecordsTable;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\ProductionRecord;
use App\Models\Area;
use App\Models\SubArea;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductionRecordResource extends Resource
{
    use HasRoleBasedAccess;

    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }
    protected static ?string $model = ProductionRecord::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCube;

    protected static string | UnitEnum | null $navigationGroup = 'CBM & Utility';

    protected static ?string $navigationLabel = 'Production Records';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'record_no';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Production Information')
                ->columns(2)
                ->components([
                    TextInput::make('record_no')
                        ->label('Record No')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated'),

                    DatePicker::make('production_date')
                        ->label('Production Date')
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
                        ->label('Area')
                        ->options(Area::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live(),

                    Select::make('sub_area_id')
                        ->label('Line')
                        ->options(fn (Get $get) => 
                            SubArea::where('area_id', $get('area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false),
                ]),

            Section::make('Production Metrics')
                ->columns(3)
                ->components([
                    TextInput::make('weight_kg')
                        ->label('Total Production (kg)')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('kg')
                        ->required(),

                    TextInput::make('good_product_kg')
                        ->label('Good Product (kg)')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('kg')
                        ->required(),

                    TextInput::make('waste_kg')
                        ->label('Waste/Reject (kg)')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('kg'),

                    TextInput::make('production_hours')
                        ->label('Production Time (minutes)')
                        ->numeric()
                        ->default(0)
                        ->suffix('min')
                        ->helperText('Total production time in minutes'),

                    TextInput::make('downtime_minutes')
                        ->label('Downtime (minutes)')
                        ->numeric()
                        ->default(0)
                        ->suffix('min'),
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
        return ProductionRecordsTable::make($table);
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
            'index' => Pages\ListProductionRecords::route('/'),
            'create' => Pages\CreateProductionRecord::route('/create'),
            'edit' => Pages\EditProductionRecord::route('/{record}/edit'),
            'view' => Pages\ViewProductionRecord::route('/{record}'),
        ];
    }
}
