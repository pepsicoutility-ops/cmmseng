<?php

namespace App\Filament\Resources\PmExecutions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class PmExecutionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('pmSchedule.code')
                    ->label('PM Code'),
                TextEntry::make('pmSchedule.title')
                    ->label('PM Title'),
                TextEntry::make('executedBy.name')
                    ->label('Executed By')
                    ->formatStateUsing(fn($record) => $record->executedBy ? "{$record->executedBy->name} ({$record->executed_by_gpid})" : '—'),
                TextEntry::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->date(),
                TextEntry::make('actual_start')
                    ->label('Actual Start')
                    ->dateTime(),
                TextEntry::make('actual_end')
                    ->label('Actual End')
                    ->dateTime()
                    ->placeholder('Not completed yet'),
                TextEntry::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn(?int $state): string => $state ? "{$state} minutes" : '—')
                    ->placeholder('—'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('compliance_status')
                    ->label('Compliance')
                    ->badge()
                    ->placeholder('Pending'),
                TextEntry::make('notes')
                    ->label('Notes')
                    ->placeholder('No notes')
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
                    
                TextEntry::make('pmSchedule.manual_url')
                    ->label('Manual Book')
                    ->url(fn($record) => $record->pmSchedule?->manual_url)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn() => 'Open Manual Book')
                    ->icon('heroicon-o-book-open')
                    ->visible(fn($record) => $record->pmSchedule?->manual_url),
            ]);
    }
}
