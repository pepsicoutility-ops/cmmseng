<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Area Name')
                    ->placeholder('e.g., Proses, Packaging, Utility'),
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->label('Area Code')
                    ->placeholder('e.g., PROC, PKG, UTL')
                    ->alphaDash(),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(3)
                    ->maxLength(1000)
                    ->label('Description'),
                Toggle::make('is_active')
                    ->label('Active Status')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
