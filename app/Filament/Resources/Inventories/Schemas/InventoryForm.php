<?php

namespace App\Filament\Resources\Inventories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Information')
                    ->schema([
                        Select::make('part_id')
                            ->label('Part')
                            ->relationship('part', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $part = \App\Models\Part::find($state);
                                    if ($part) {
                                        $set('min_stock', $part->min_stock);
                                        $set('location', $part->location);
                                    }
                                }
                            }),
                        TextInput::make('quantity')
                            ->label('Current Stock at This Location')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('min_stock')
                            ->label('Minimum Stock (from Part)')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('This value is synced from the Part'),
                        TextInput::make('max_stock')
                            ->label('Maximum Stock')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(0),
                        TextInput::make('location')
                            ->label('Storage Location (from Part)')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('This value is synced from the Part'),
                        DateTimePicker::make('last_restocked_at')
                            ->label('Last Restocked')
                            ->displayFormat('d/m/Y H:i'),
                    ])->columns(2),
                Section::make('Equipment Location (Optional)')
                    ->description('Link inventory to specific equipment location')
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->relationship('area', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('sub_area_id', null)),
                        Select::make('sub_area_id')
                            ->label('Sub Area')
                            ->relationship('subArea', 'name', fn ($query, Get $get) => 
                                $query->where('area_id', $get('area_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('asset_id', null))
                            ->disabled(fn (Get $get) => !$get('area_id')),
                        Select::make('asset_id')
                            ->label('Asset')
                            ->relationship('asset', 'name', fn ($query, Get $get) => 
                                $query->where('sub_area_id', $get('sub_area_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('sub_asset_id', null))
                            ->disabled(fn (Get $get) => !$get('sub_area_id')),
                        Select::make('sub_asset_id')
                            ->label('Sub Asset')
                            ->relationship('subAsset', 'name', fn ($query, Get $get) => 
                                $query->where('asset_id', $get('asset_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('asset_id')),
                    ])->columns(2)->collapsible(),
            ]);
    }
}
