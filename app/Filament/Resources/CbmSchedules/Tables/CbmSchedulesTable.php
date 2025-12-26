<?php

namespace App\Filament\Resources\CbmSchedules\Tables;

use App\Models\CbmSchedule;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class CbmSchedulesTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schedule_no')
                    ->label('Schedule No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('checklist_type_label')
                    ->label('Checklist Type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Compressor 1', 'Compressor 2' => 'info',
                        'Chiller 1', 'Chiller 2' => 'primary',
                        'AHU' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('frequency_label')
                    ->label('Frequency')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('shifts_per_day')
                    ->label('Shifts')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->placeholder('No end date'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
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

                Tables\Filters\SelectFilter::make('frequency')
                    ->options([
                        'per_shift' => 'Per Shift',
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),

                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggle_active')
                    ->label(fn (CbmSchedule $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (CbmSchedule $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (CbmSchedule $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (CbmSchedule $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
