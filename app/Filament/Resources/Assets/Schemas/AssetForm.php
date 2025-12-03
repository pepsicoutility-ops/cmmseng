<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Area;
use App\Models\SubArea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Schema;

class AssetForm
{
    public static function configure(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Section::make('Location Information')
                    ->description('Select the area and sub-area for this asset')
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->options(Area::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('sub_area_id', null))
                            ->required()
                            ->native(false),
                        Select::make('sub_area_id')
                            ->label('Sub Area')
                            ->options(fn (Get $get) => SubArea::query()
                                ->where('area_id', $get('area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->disabled(fn (Get $get) => !$get('area_id')),
                    ])->columns(2),
                    
                Section::make('Asset Information')
                    ->description('Enter the asset details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Asset Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Processing Unit, VMM, EXTRUDER'),
                        TextInput::make('code')
                            ->label('Asset Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., AST-PROC-001')
                            ->alphaDash(),
                        TextInput::make('model')
                            ->label('Model')
                            ->maxLength(100)
                            ->placeholder('e.g., PRO-500X'),
                        TextInput::make('serial_number')
                            ->label('Serial Number')
                            ->maxLength(100)
                            ->placeholder('e.g., SN-PROC-2024-001'),
                        DatePicker::make('installation_date')
                            ->label('Installation Date')
                            ->native(false)
                            ->displayFormat('d M Y'),
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
