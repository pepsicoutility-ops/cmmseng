<?php

namespace App\Filament\Resources\SubAssets\Schemas;

use App\Models\Area;
use App\Models\SubArea;
use App\Models\Asset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SubAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Location Information')
                    ->description('Select the complete hierarchy for this sub-asset')
                    ->components([
                        Select::make('area_id')
                            ->label('Area')
                            ->options(Area::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('sub_area_id', null);
                                $set('asset_id', null);
                            })
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
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('asset_id', null))
                            ->required()
                            ->native(false)
                            ->disabled(fn (Get $get) => !$get('area_id')),
                        Select::make('asset_id')
                            ->label('Asset')
                            ->options(fn (Get $get) => Asset::query()
                                ->where('sub_area_id', $get('sub_area_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->disabled(fn (Get $get) => !$get('sub_area_id')),
                    ])->columns(3),
                    
                Section::make('Sub Asset Information')
                    ->description('Enter the sub-asset details')
                    ->components([
                        TextInput::make('name')
                            ->label('Sub Asset Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Fryer, S9 Overview'),
                        TextInput::make('code')
                            ->label('Sub Asset Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., SUB-FRY-001')
                            ->alphaDash(),
                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(1000),
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
