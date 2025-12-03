<?php

namespace App\Filament\Resources\PmCompliances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class PmComplianceForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period')
                    ->options(['week' => 'Week', 'month' => 'Month'])
                    ->required(),
                DatePicker::make('period_start')
                    ->required(),
                DatePicker::make('period_end')
                    ->required(),
                TextInput::make('total_pm')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('completed_pm')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('overdue_pm')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('compliance_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
