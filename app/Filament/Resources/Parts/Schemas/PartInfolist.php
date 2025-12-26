<?php

namespace App\Filament\Resources\Parts\Schemas;

use App\Models\Part;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PartInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('part_number'),
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('category')
                    ->placeholder('-'),
                TextEntry::make('unit'),
                TextEntry::make('min_stock')
                    ->numeric(),
                TextEntry::make('current_stock')
                    ->numeric(),
                TextEntry::make('unit_price')
                    ->money(),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Part $record): bool => $record->trashed()),
            ]);
    }
}
