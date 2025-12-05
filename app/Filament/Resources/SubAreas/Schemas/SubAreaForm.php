<?php

namespace App\Filament\Resources\SubAreas\Schemas;

use App\Models\Area;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('area_id')
                    ->label('Area')
                    ->options(Area::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
                TextInput::make('name')
                    ->label('Line')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., EP, PC, TC, DBM, LBCSS'),
                TextInput::make('code')
                    ->label('Line Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('e.g., EP, PC')
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
            ])
            ->columns(2);
    }
}
