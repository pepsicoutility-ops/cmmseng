<?php

namespace App\Filament\Resources\PmExecutions\Tables;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PmExecutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('pmSchedule.code')
                    ->label('PM Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pmSchedule.title')
                    ->label('PM Title')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('executedBy.name')
                    ->label('Executed By')
                    ->formatStateUsing(fn ($record) => $record->executedBy ? "{$record->executedBy->name} ({$record->executed_by_gpid})" : '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date()
                    ->sortable(),
                TextColumn::make('actual_start')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('actual_end')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '—')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('compliance_status')
                    ->label('Compliance')
                    ->badge()
                    ->colors([
                        'success' => 'on_time',
                        'danger' => 'late',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : '—')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                    ])
                    ->multiple(),
                SelectFilter::make('compliance_status')
                    ->label('Compliance')
                    ->options([
                        'on_time' => 'On Time',
                        'late' => 'Late',
                    ])
                    ->multiple(),
                Filter::make('scheduled_date')
                    ->schema([
                        DatePicker::make('scheduled_from')
                            ->label('Scheduled From'),
                        DatePicker::make('scheduled_until')
                            ->label('Scheduled Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['scheduled_from'], fn ($q, $date) => $q->whereDate('scheduled_date', '>=', $date))
                            ->when($data['scheduled_until'], fn ($q, $date) => $q->whereDate('scheduled_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'in_progress'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])
                 ->visible(fn () => Auth::user()->role !== 'technician'),
                ])
            ->defaultSort('scheduled_date', 'desc');
    }
}
