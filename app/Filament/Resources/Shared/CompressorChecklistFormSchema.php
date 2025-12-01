<?php

namespace App\Filament\Resources\Shared;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\User;

class CompressorChecklistFormSchema
{
    public static function getSchema(): array
    {
        return [
            Section::make('Basic Information')
                ->description('Operator details and shift information')
                ->columns(3)
                ->schema([
                    Select::make('shift')
                        ->label('Shift')
                        ->options([
                            '1' => 'Shift 1',
                            '2' => 'Shift 2',
                            '3' => 'Shift 3',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Current work shift'),
                    
                    TextInput::make('gpid')
                        ->label('GPID')
                        ->maxLength(255)
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $user = User::where('gpid', $state)->first();
                                $set('name', $user?->name);
                            } else {
                                $set('name', null);
                            }
                        })
                        ->helperText('Enter operator GPID'),
                    
                    TextInput::make('name')
                        ->label('Operator Name')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-filled from GPID'),
                ]),
            
            Section::make('Operating Parameters')
                ->description('Compressor running hours')
                ->columns(3)
                ->schema([
                    TextInput::make('tot_run_hours')
                        ->label('Total Run Hours')
                        ->numeric()
                        ->suffix('hrs')
                        ->step(0.1)
                        ->helperText('Total operating hours'),
                ]),
            
            Section::make('Temperature & Pressure Readings')
                ->description('Bearing and discharge measurements')
                ->columns(2)
                ->schema([
                    TextInput::make('bearing_oil_temperature')
                        ->label('Bearing Oil Temperature')
                        ->numeric()
                        ->suffix('°C')
                        ->step(0.01)
                        ->helperText('Bearing oil temp'),
                    
                    TextInput::make('bearing_oil_pressure')
                        ->label('Bearing Oil Pressure')
                        ->numeric()
                        ->suffix('bar')
                        ->step(0.01)
                        ->helperText('Bearing oil pressure'),
                    
                    TextInput::make('discharge_pressure')
                        ->label('Discharge Pressure')
                        ->numeric()
                        ->suffix('bar')
                        ->step(0.01)
                        ->helperText('Discharge pressure'),
                    
                    TextInput::make('discharge_temperature')
                        ->label('Discharge Temperature')
                        ->numeric()
                        ->suffix('°C')
                        ->step(0.01)
                        ->helperText('Discharge temp'),
                ]),
            
            Section::make('Cooling Water System')
                ->description('Cooling water supply and return measurements')
                ->columns(2)
                ->schema([
                    TextInput::make('cws_temperature')
                        ->label('CWS Temperature')
                        ->numeric()
                        ->suffix('°C')
                        ->step(0.01)
                        ->helperText('Cooling water supply temp'),
                    
                    TextInput::make('cwr_temperature')
                        ->label('CWR Temperature')
                        ->numeric()
                        ->suffix('°C')
                        ->step(0.01)
                        ->helperText('Cooling water return temp'),
                    
                    TextInput::make('cws_pressure')
                        ->label('CWS Pressure')
                        ->numeric()
                        ->suffix('bar')
                        ->step(0.01)
                        ->helperText('Cooling water supply pressure'),
                    
                    TextInput::make('cwr_pressure')
                        ->label('CWR Pressure')
                        ->numeric()
                        ->suffix('bar')
                        ->step(0.01)
                        ->helperText('Cooling water return pressure'),
                ]),
            
            Section::make('Refrigerant System')
                ->description('Refrigerant and dew point measurements')
                ->columns(2)
                ->schema([
                    TextInput::make('refrigerant_pressure')
                        ->label('Refrigerant Pressure')
                        ->numeric()
                        ->suffix('bar')
                        ->step(0.01)
                        ->helperText('Refrigerant pressure'),
                    
                    TextInput::make('dew_point')
                        ->label('Dew Point')
                        ->numeric()
                        ->suffix('°C')
                        ->step(0.01)
                        ->helperText('Dew point temperature'),
                ]),
            
            Section::make('Additional Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Any observations or issues'),
                ]),
        ];
    }
}
