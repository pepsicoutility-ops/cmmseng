<?php

namespace App\Filament\Resources\PmSchedules\Schemas;

use App\Models\PmSchedule;
use App\Models\Area;
use App\Models\Asset;
use App\Models\SubArea;
use App\Models\SubAsset;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PmScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('PM Information')
                    ->schema([
                        TextInput::make('code')
                            ->label('PM Code')
                            ->default(fn () => self::generatePmCode())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('title')
                            ->label('PM Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Section::make('Equipment')
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->options(Area::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Set $set) {
                                $set('sub_area_id', null);
                                $set('asset_id', null);
                                $set('sub_asset_id', null);
                            })
                            ->native(false),
                        Select::make('sub_area_id')
                            ->label('Line')
                            ->options(fn (Get $get) => SubArea::query()
                                ->where('area_id', $get('area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Set $set) {
                                $set('asset_id', null);
                                $set('sub_asset_id', null);
                            })
                            ->disabled(fn (Get $get) => !$get('area_id'))
                            ->native(false),
                        Select::make('asset_id')
                            ->label('Machine')
                            ->options(fn (Get $get) => Asset::query()
                                ->where('sub_area_id', $get('sub_area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (Set $set) => $set('sub_asset_id', null))
                            ->disabled(fn (Get $get) => !$get('sub_area_id'))
                            ->native(false),
                        Select::make('sub_asset_id')
                            ->label('Equipment (Optional)')
                            ->options(fn (Get $get) => SubAsset::query()
                                ->where('asset_id', $get('asset_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('asset_id'))
                            ->native(false),
                    ])->columns(2),
                    
                Section::make('Schedule')
                    ->schema([
                        Select::make('schedule_type')
                            ->label('Type')
                            ->options([
                                'weekly' => 'Weekly',
                                'running_hours' => 'Running Hours',
                                'cycle' => 'Cycle',
                            ])
                            ->default('weekly')
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('frequency')
                            ->label('Frequency')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(fn (Get $get) => $get('schedule_type') === 'weekly' ? 52 : null)
                            ->helperText(fn (Get $get) => $get('schedule_type') === 'weekly' 
                                ? 'Max 52 weeks (1 year)' 
                                : ($get('schedule_type') === 'running_hours' 
                                    ? 'Enter running hours threshold (e.g., 500 for every 500 hours)' 
                                    : 'Enter cycle count threshold (e.g., 1000 for every 1000 cycles)')),
                        Select::make('week_day')
                            ->label('Day (Optional)')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->visible(fn (Get $get) => $get('schedule_type') === 'weekly')
                            ->helperText('Can be set later according to production planning')
                            ->native(false),
                        TextInput::make('estimated_duration')
                            ->label('Duration (min)')
                            ->required()
                            ->numeric()
                            ->default(60)
                            ->minValue(1),
                    ])->columns(3),
                    
                Section::make('Assignment')
                    ->schema([
                        Select::make('department')
                            ->label('Department')
                            ->options([
                                'utility' => 'Utility',
                                'electric' => 'Electric',
                                'mechanic' => 'Mechanic',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assigned_to_gpid', null))
                            ->native(false),
                        Select::make('assigned_to_gpid')
                            ->label('Assigned To')
                            ->options(fn (Get $get) => User::query()
                                ->where('role', 'technician')
                                ->where('department', $get('department'))
                                ->where('is_active', true)
                                ->pluck('name', 'gpid'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Get $get) => !$get('department'))
                            ->native(false),
                        TextInput::make('assigned_by_gpid')
                            ->label('Assigned By')
                            ->default(fn () => Auth::user()->gpid)
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'completed' => 'Completed',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                        TextInput::make('manual_url')
                            ->label('Manual Book URL (PDF)')
                            ->url()
                            ->placeholder('https://example.com/manual.pdf')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
    
    private static function generatePmCode(): string
    {
        $date = now()->format('Ym');
        $count = PmSchedule::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
            
        return "PM-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
