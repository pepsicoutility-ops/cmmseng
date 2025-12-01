<?php

namespace App\Filament\Resources\BarcodeTokens\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class BarcodeTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Barcode Token Information')
                    ->components([
                        TextInput::make('token')
                            ->label('Token')
                            ->disabled()
                            ->helperText('Token will be auto-generated'),
                        Select::make('department')
                            ->label('Department')
                            ->options([
                                'all' => 'All Departments',
                                'utility' => 'Utility',
                                'mechanic' => 'Mechanic',
                                'electric' => 'Electric',
                            ])
                            ->default('all')
                            ->helperText('Select department for this token')
                            ->required(),
                        Checkbox::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->inline(),
                    ])->columns(1),
            ]);
    }
}
