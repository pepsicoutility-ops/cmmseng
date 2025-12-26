<?php

namespace App\Filament\Resources\PmSchedules\Tables;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use App\Models\PmExecution;
use Filament\Notifications\Notification;
use App\Filament\Resources\PmExecutions\PmExecutionResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PmSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('PM Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('asset.name')
                    ->label('Machine')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subAsset.name')
                    ->label('Equipment')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->formatStateUsing(fn ($record) => $record->assignedTo ? "{$record->assignedTo->name} (GPID: {$record->assigned_to_gpid})" : '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'weekly',
                        'success' => 'running_hours',
                        'warning' => 'cycle',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('week_day')
                    ->label('Day')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                    ->placeholder('—'),
                TextColumn::make('estimated_duration')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state): string => "{$state} min")
                    ->sortable(),
                TextColumn::make('department')
                    ->badge()
                    ->colors([
                        'success' => 'utility',
                        'warning' => 'electric',
                        'danger' => 'mechanic',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'primary' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->isOverdue() => 'danger',
                        $record->isDueSoon() => 'warning',
                        default => 'success',
                    })
                    ->icon(fn ($record) => match(true) {
                        $record->isOverdue() => 'heroicon-o-exclamation-triangle',
                        $record->isDueSoon() => 'heroicon-o-clock',
                        default => 'heroicon-o-check-circle',
                    })
                    ->sortable()
                    ->tooltip(fn ($record) => match(true) {
                        $record->isOverdue() => '🔴 Overdue! Harus dikerjakan segera',
                        $record->isDueSoon() => '⚠️ Due soon - dalam 2 hari',
                        default => '✅ On track',
                    }),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label('Department')
                    ->options([
                        'utility' => 'Utility',
                        'electric' => 'Electric',
                        'mechanic' => 'Mechanic',
                    ])
                    ->multiple(),
                SelectFilter::make('week_day')
                    ->label('Day')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->multiple(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),
                SelectFilter::make('assigned_to_gpid')
                    ->label('Assigned To')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(fn () => in_array(Auth::user()->role, ['manager', 'super_admin', 'asisten_manager'])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => 
                        Auth::user()->role !== 'technician'
                    ),
                Action::make('execute')
                    ->label('Execute PM')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Execute PM')
                    ->modalDescription(fn ($record) => 'Start executing PM: ' . $record->code)
                    ->action(function ($record) {
                        // Create PM Execution record
                        $execution = PmExecution::create([
                            'pm_schedule_id' => $record->id,
                            'executed_by_gpid' => Auth::user()->gpid,
                            'scheduled_date' => now(),
                            'actual_start' => now(),
                            'status' => 'in_progress',
                            'checklist_data' => $record->checklistItems->map(function ($item) {
                                return [
                                    'item_id' => $item->id,
                                    'description' => $item->description,
                                    'type' => $item->type,
                                    'is_completed' => false,
                                    'value' => null,
                                ];
                            })->toArray(),
                        ]);
                        
                        Notification::make()
                            ->title('PM Execution Started')
                            ->body('PM Execution has been created. You can edit it from PM Executions list.')
                            ->success()
                            ->send();
                        
                        // Redirect to PM Executions table
                        return redirect(PmExecutionResource::getUrl('index'));
                    })
                    ->visible(fn ($record) => 
                        $record->status === 'active' && 
                        Auth::user()->role === 'technician' &&
                        $record->assigned_to_gpid === Auth::user()->gpid
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])
                ->visible(fn () => Auth::user()->role !== 'technician'),
            ]);
    }
}
