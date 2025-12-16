<?php

namespace App\Filament\Resources\Shared;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;

class ChillerChecklistFormSchema
{
    public static function getSchema(): array
    {
        return [
            Section::make('Basic Information')
                ->schema([
                    Select::make('shift')
                        ->options([
                            1 => 'Shift 1',
                            2 => 'Shift 2',
                            3 => 'Shift 3',
                        ])
                        ->required(),
                    
                    TextInput::make('gpid')
                        ->label('GPID')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $user = User::where('gpid', $state)->first();
                                if ($user) {
                                    $set('name', $user->name);
                                }
                            }
                        }),
                    
                    TextInput::make('name')
                        ->label('Operator Name')
                        ->readOnly(),
                ])
                ->columns(3),

            Section::make('Temperature & Pressure')
                ->schema([
                    TextInput::make('sat_evap_t')
                        ->label('Sat. Evap. T (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('sat_dis_t')
                        ->label('Sat. Dis. T (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('dis_superheat')
                        ->label('Dis. SuperHeat (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('evap_p')
                        ->label('Evap. P (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('conds_p')
                        ->label('Conds. P (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('oil_p')
                        ->label('Oil. P (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('evap_t_diff')
                        ->label('Evap. T Diff (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('conds_t_diff')
                        ->label('Conds. T Diff (°C)')
                        ->numeric()
                        ->step(0.01),
                ])
                ->columns(4),

            Section::make('Current & Load')
                ->schema([
                    TextInput::make('lcl')
                        ->label('LCL (A)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('fla')
                        ->label('FLA (Full Load Amps)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('ecl')
                        ->label('ECL (A)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('lel')
                        ->label('LEL (A)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('eel')
                        ->label('EEL (A)')
                        ->numeric()
                        ->step(0.01),
                ])
                ->columns(5),

            Section::make('Motor & System')
                ->schema([
                    TextInput::make('reff_levels')
                        ->label('Reff. Levels')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('motor_amps')
                        ->label('Motor Amps (A)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('motor_volts')
                        ->label('Motor Volts (V)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('heatsink_t')
                        ->label('Heatsink T (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('run_hours')
                        ->label('Run Hours (hrs)')
                        ->numeric()
                        ->step(0.1),
                    
                    TextInput::make('motor_t')
                        ->label('Motor T (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('comp_oil_level')
                        ->label('Comp Oil Level'),
                ])
                ->columns(4),

            Section::make('Cooler Parameters')
                ->schema([
                    TextInput::make('cooler_reff_small_temp_diff')
                        ->label('reff Small Temp Diff (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cooler_liquid_inlet_pressure')
                        ->label('Liquid Inlet Pressure (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cooler_liquid_outlet_pressure')
                        ->label('Liquid Outlet Pressure (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cooler_pressure_drop')
                        ->label('Pressure Drop (kPa)')
                        ->numeric()
                        ->step(0.01),
                ])
                ->columns(4),

            Section::make('Condenser Parameters')
                ->schema([
                    TextInput::make('cond_reff_small_temp_diff')
                        ->label('reff Small Temp Diff (°C)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cond_liquid_inlet_pressure')
                        ->label('Liquid Inlet Pressure (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cond_liquid_outlet_pressure')
                        ->label('Liquid Outlet Pressure (kPa)')
                        ->numeric()
                        ->step(0.01),
                    
                    TextInput::make('cond_pressure_drop')
                        ->label('Pressure Drop (kPa)')
                        ->numeric()
                        ->step(0.01),
                ])
                ->columns(4),

            Section::make('Additional Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
