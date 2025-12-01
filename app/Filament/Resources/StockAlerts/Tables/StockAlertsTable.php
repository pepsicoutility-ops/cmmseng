<?php

namespace App\Filament\Resources\StockAlerts\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class StockAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('triggered_at')
                    ->label('Triggered')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('part.part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('part.name')
                    ->label('Part Name')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('alert_type')
                    ->label('Alert Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                TextColumn::make('part.current_stock')
                    ->label('Current Stock')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('part.min_stock')
                    ->label('Min Stock')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_resolved')
                    ->label('Resolved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('alert_type')
                    ->label('Alert Type')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                    ]),
                TernaryFilter::make('is_resolved')
                    ->label('Resolved')
                    ->placeholder('All alerts')
                    ->trueLabel('Resolved only')
                    ->falseLabel('Unresolved only'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_resolved' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Alert Resolved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->is_resolved),
                \Filament\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.pep.resources.inventories.index', [
                        'tableSearch' => $record->part->part_number
                    ]))
                    ->visible(fn ($record) => !$record->is_resolved),
            ])
            ->defaultSort('triggered_at', 'desc');
    }
}
