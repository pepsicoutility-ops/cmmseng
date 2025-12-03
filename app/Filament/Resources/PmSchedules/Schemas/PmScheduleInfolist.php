<?php

namespace App\Filament\Resources\PmSchedules\Schemas;

use App\Models\PmSchedule;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PmScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('code'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('area.name')
                    ->label('Area')
                    ->placeholder('-'),
                TextEntry::make('subArea.name')
                    ->label('Sub area')
                    ->placeholder('-'),
                TextEntry::make('asset.name')
                    ->label('Asset')
                    ->placeholder('-'),
                TextEntry::make('subAsset.name')
                    ->label('Sub asset')
                    ->placeholder('-'),
                TextEntry::make('schedule_type')
                    ->badge(),
                TextEntry::make('frequency')
                    ->numeric(),
                TextEntry::make('week_day')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('estimated_duration')
                    ->numeric(),
                TextEntry::make('assigned_to_gpid')
                    ->placeholder('-'),
                TextEntry::make('assigned_by_gpid')
                    ->placeholder('-'),
                TextEntry::make('department')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (PmSchedule $record): bool => $record->trashed()),
            ]);
    }
}
