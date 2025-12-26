<?php

namespace App\Filament\Resources\AreaOwnerResource\Schemas;

use App\Models\Area;
use App\Models\SubArea;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AreaOwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Area Ownership')
                ->components([
                    Select::make('area_id')
                        ->label('Area')
                        ->options(Area::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => $set('line_ids', [])),

                    Select::make('line_ids')
                        ->label('Lines')
                        ->multiple()
                        ->options(function (Get $get) {
                            $areaId = $get('area_id');
                            if (!$areaId) {
                                return [];
                            }
                            return SubArea::where('area_id', $areaId)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Select one or more lines (optional)'),

                    Select::make('owner_gpid')
                        ->label('Owner')
                        ->options(User::whereIn('role', ['technician', 'engineer', 'supervisor'])
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->gpid => $user->name . ' (' . $user->gpid . ')']))
                        ->required()
                        ->searchable()
                        ->preload(),

                    DatePicker::make('assigned_date')
                        ->label('Assigned Date')
                        ->required()
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->maxDate(now()),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->required(),
                ])->columns(2),
        ]);
    }
}
