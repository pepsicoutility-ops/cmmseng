<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\WorkOrder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;

class WorkOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('wo_number'),
                TextEntry::make('created_by_gpid')
                    ->placeholder('-'),
                TextEntry::make('operator_name'),
                TextEntry::make('shift')
                    ->badge(),
                TextEntry::make('problem_type')
                    ->badge(),
                TextEntry::make('assign_to')
                    ->badge(),
                TextEntry::make('area.name')
                    ->label('Area'),
                TextEntry::make('subArea.name')
                    ->label('Lines'),
                TextEntry::make('asset.name')
                    ->label('Machine'),
                TextEntry::make('subAsset.name')
                    ->label('Equipment'),
                TextEntry::make('description')
                    ->columnSpanFull(),
               Section::make('Photos')
                    ->schema([
                        ViewEntry::make('photos')
                            ->label('')
                            ->view('filament.pm.photos-display')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn($record) => $record->photos && count($record->photos) > 0),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('priority')
                    ->badge(),
                TextEntry::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('closed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('total_downtime')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('mttr')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (WorkOrder $record): bool => $record->trashed()),
            ]);
    }
}
