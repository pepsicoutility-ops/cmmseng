<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Area;
use App\Models\Asset;
use App\Models\SubArea;
use App\Models\SubAsset;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Work Order Information')
                    ->components([
                        TextInput::make('wo_number')
                            ->label('WO Number')
                            ->default(fn () => self::generateWoNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('operator_name')
                            ->label('Operator Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('shift')
                            ->label('Shift')
                            ->options([
                                '1' => 'Shift 1',
                                '2' => 'Shift 2',
                                '3' => 'Shift 3',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('created_by_gpid')
                            ->label('Created By GPID')
                            ->default(fn () => Auth::user()->gpid)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),
                    
                Section::make('Problem Details')
                    ->schema([
                        Select::make('problem_type')
                            ->label('Problem Type')
                            ->options([
                                'abnormality' => 'Abnormality',
                                'breakdown' => 'Breakdown',
                                'request_consumable' => 'Request Consumable',
                                'improvement' => 'Improvement',
                                'inspection' => 'Inspection',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('assign_to')
                            ->label('Assign To Department')
                            ->options([
                                'utility' => 'Utility',
                                'mechanic' => 'Mechanic',
                                'electric' => 'Electric',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->default('medium')
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->label('Problem Description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('photos')
                            ->label('Photos')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('work-orders')
                            ->columnSpanFull(),
                    ])->columns(3),
                    
                Section::make('Equipment Location')
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->options(Area::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (callable $set) {
                                $set('sub_area_id', null);
                                $set('asset_id', null);
                                $set('sub_asset_id', null);
                            })
                            ->disabled(fn ($record) => $record !== null)
                            ->native(false),
                        Select::make('sub_area_id')
                            ->label('Sub Area')
                            ->options(fn (Get $get) => SubArea::query()
                                ->where('area_id', $get('area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (callable $set) {
                                $set('asset_id', null);
                                $set('sub_asset_id', null);
                            })
                            ->disabled(fn (Get $get, $record) => !$get('area_id') || $record !== null)
                            ->native(false),
                        Select::make('asset_id')
                            ->label('Asset')
                            ->options(fn (Get $get) => Asset::query()
                                ->where('sub_area_id', $get('sub_area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('sub_asset_id', null))
                            ->disabled(fn (Get $get, $record) => !$get('sub_area_id') || $record !== null)
                            ->native(false),
                        Select::make('sub_asset_id')
                            ->label('Sub Asset (Optional)')
                            ->options(fn (Get $get) => SubAsset::query()
                                ->where('asset_id', $get('asset_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get, $record) => !$get('asset_id') || $record !== null)
                            ->native(false),
                    ])->columns(2)
                    ->description('Equipment location cannot be changed after creation'),
                    
                Section::make('Status & Timeline')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'submitted' => 'Submitted',
                                'reviewed' => 'Reviewed',
                                'approved' => 'Approved',
                                'in_progress' => 'In Progress',
                                'on_hold' => 'On Hold',
                                'completed' => 'Completed',
                                'closed' => 'Closed',
                            ])
                            ->default('submitted')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->native(false),
                        DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->native(false),
                        DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->native(false),
                        DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->native(false),
                        DateTimePicker::make('closed_at')
                            ->label('Closed At')
                            ->native(false),
                        TextInput::make('total_downtime')
                            ->label('Total Downtime (min)')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('mttr')
                            ->label('MTTR (min)')
                            ->numeric()
                            ->disabled(),
                    ])->columns(3)
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null),
            ]);
    }
    
    private static function generateWoNumber(): string
    {
        $date = now()->format('Ym');
        $count = \App\Models\WorkOrder::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
            
        return "WO-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
