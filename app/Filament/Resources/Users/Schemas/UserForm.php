<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->description('Basic user details and credentials')
                    ->components([
                        TextInput::make('gpid')
                            ->label('GPID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('e.g., SA001, MGR001, TCM001')
                            ->helperText('Unique identifier for the user')
                            ->alphaDash(),
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., John Doe'),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('user@cmms.com'),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('e.g., +62 812-3456-7890'),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->placeholder('Enter password')
                            ->helperText('Leave empty to keep current password when editing'),
                    ])->columns(2),
                    
                Section::make('Role & Department')
                    ->description('User role and department assignment')
                    ->components([
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'manager' => 'Manager',
                                'asisten_manager' => 'Asisten Manager',
                                'technician' => 'Technician',
                                'tech_store' => 'Tech Store',
                                'operator' => 'Operator',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Clear department if role doesn't need it
                                if (!in_array($state, ['asisten_manager', 'technician'])) {
                                    $set('department', null);
                                }
                            })
                            ->helperText('Select the user role'),
                        Select::make('department')
                            ->label('Department')
                            ->options([
                                'utility' => 'Utility',
                                'electric' => 'Electric',
                                'mechanic' => 'Mechanic',
                            ])
                            ->native(false)
                            ->required(fn (Get $get): bool => in_array($get('role'), ['asisten_manager', 'technician']))
                            ->visible(fn (Get $get): bool => in_array($get('role'), ['asisten_manager', 'technician']))
                            ->helperText('Required for Asisten Manager and Technician roles'),
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->required()
                            ->helperText('Inactive users cannot login'),
                    ])->columns(3),
            ]);
    }
}
