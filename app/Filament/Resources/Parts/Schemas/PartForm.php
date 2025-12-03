<?php

namespace App\Filament\Resources\Parts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
class PartForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Part Information')
                    ->description('Basic part details and identification')
                    ->schema([
                        TextInput::make('part_number')
                            ->label('Part Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., E-001, M-002, C-003')
                            ->alphaDash(),
                        TextInput::make('name')
                            ->label('Part Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Bearing, Motor, Filter'),
                        Select::make('category')
                            ->label('Category')
                            ->options([
                                'electric' => 'Electric',
                                'mechanic' => 'Mechanic',
                                'consumable' => 'Consumable',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->native(false)
                            ->placeholder('Select category'),
                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(1000),
                    ])->columns(2),
                    
                Section::make('Inventory Information')
                    ->description('Stock levels and pricing')
                    ->schema([
                        TextInput::make('unit')
                            ->label('Unit')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('e.g., pcs, set, kg, liter'),
                        TextInput::make('min_stock')
                            ->label('Minimum Stock')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('units')
                            ->helperText('Alert will be triggered when stock below this level'),
                        TextInput::make('current_stock')
                            ->label('Current Stock')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('units'),
                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->minValue(0)
                            ->prefix('IDR')
                            ->step(100),
                        TextInput::make('location')
                            ->label('Storage Location')
                            ->maxLength(100)
                            ->placeholder('e.g., Warehouse A, Rack B3'),
                    ])->columns(2),
            ]);
    }
}
