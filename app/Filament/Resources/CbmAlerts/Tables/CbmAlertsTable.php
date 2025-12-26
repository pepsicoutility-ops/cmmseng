<?php

namespace App\Filament\Resources\CbmAlerts\Tables;

use App\Models\CbmAlert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;



class CbmAlertsTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alert_no')
                    ->label('Alert No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('checklist_type_label')
                    ->label('Checklist')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('parameter_name')
                    ->label('Parameter')
                    ->searchable()
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('recorded_value')
                    ->label('Value')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('threshold_value')
                    ->label('Threshold')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('alert_type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn (CbmAlert $record) => match($record->alert_type) {
                        'below_min', 'above_max' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'open' => 'danger',
                        'acknowledged' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Detected')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('checklist_type')
                    ->label('Checklist Type')
                    ->options([
                        'compressor1' => 'Compressor 1',
                        'compressor2' => 'Compressor 2',
                        'chiller1' => 'Chiller 1',
                        'chiller2' => 'Chiller 2',
                        'ahu' => 'AHU',
                    ]),

                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'warning' => 'Warning',
                        'info' => 'Info',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'acknowledged' => 'Acknowledged',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->default('open'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->visible(fn (CbmAlert $record) => $record->status === 'open')
                    ->requiresConfirmation()
                    ->action(function (CbmAlert $record) {
                        $record->acknowledge();
                        Notification::make()
                            ->title('Alert acknowledged')
                            ->success()
                            ->send();
                    }),

                Action::make('start_progress')
                    ->label('Start Progress')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (CbmAlert $record) => $record->status === 'acknowledged')
                    ->requiresConfirmation()
                    ->action(function (CbmAlert $record) {
                        $record->startProgress();
                        Notification::make()
                            ->title('Working on alert')
                            ->success()
                            ->send();
                    }),

                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CbmAlert $record) => in_array($record->status, ['acknowledged', 'in_progress']))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CbmAlert $record, array $data) {
                        $record->resolve($data['resolution_notes']);
                        Notification::make()
                            ->title('Alert resolved')
                            ->success()
                            ->send();
                    }),

                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (CbmAlert $record) => $record->status === 'resolved')
                    ->requiresConfirmation()
                    ->action(function (CbmAlert $record) {
                        $record->close();
                        Notification::make()
                            ->title('Alert closed')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('acknowledge_bulk')
                        ->label('Acknowledge Selected')
                        ->icon('heroicon-o-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->status === 'open' && $record->acknowledge());
                            Notification::make()
                                ->title('Alerts acknowledged')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
