<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WoProcessesRelationManager extends RelationManager
{
    protected static string $relationship = 'processes';
    
    protected static ?string $title = 'Process History';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // No form - processes are created automatically via actions
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->colors([
                        'info' => 'review',
                        'primary' => 'approve',
                        'warning' => 'start',
                        'danger' => 'hold',
                        'success' => ['continue', 'complete'],
                        'gray' => 'close',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('performedBy.name')
                    ->label('Performed By')
                    ->formatStateUsing(fn ($record) => $record->performedBy ? "{$record->performedBy->name} ({$record->performed_by_gpid})" : $record->performed_by_gpid)
                    ->searchable(),
                TextColumn::make('timestamp')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->defaultSort('timestamp', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - processes are automatic
            ])
            ->recordActions([
                // No edit/delete actions
            ])
            ->toolbarActions([
                // No bulk actions
            ])
            ->emptyStateHeading('No process history yet')
            ->emptyStateDescription('Process history will appear here when actions are performed on this work order.');
    }
}
