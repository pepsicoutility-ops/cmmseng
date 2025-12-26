<?php

namespace App\Filament\Resources\EquipmentTroubles\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EquipmentTroublesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('technicians'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('equipment.name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                
                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                
                BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'critical',
                    ])
                    ->icons([
                        'heroicon-o-arrow-down' => 'low',
                        'heroicon-o-minus' => 'medium',
                        'heroicon-o-arrow-up' => 'high',
                        'heroicon-o-exclamation-triangle' => 'critical',
                    ]),
                
                BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'warning' => 'investigating',
                        'info' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ]),
                
                TextColumn::make('reportedBy.name')
                    ->label('Reported By')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('technicians.name')
                    ->label('Assigned Technicians')
                    ->badge()
                    ->separator(',')
                    ->default('-')
                    ->toggleable(),
                
                TextColumn::make('reported_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('response_time')
                    ->label('Response Time')
                    ->suffix(' min')
                    ->default('-')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('resolution_time')
                    ->label('Resolution Time')
                    ->suffix(' min')
                    ->default('-')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('downtime_minutes')
                    ->label('Downtime')
                    ->suffix(' min')
                    ->default('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('reported_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'investigating' => 'Investigating',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->default('open'),
                
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                
                SelectFilter::make('equipment_id')
                    ->relationship('equipment', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Equipment'),
            ])
            ->recordActions([
                // Start Investigating (Open -> Investigating)
                Action::make('investigate')
                    ->label('Investigate')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->visible(function ($record) {
                        if ($record->status !== 'open') {
                            return false;
                        }
                        
                        $user = Auth::user();
                        
                        // Manager dan asisten_manager selalu bisa lihat
                        if (in_array($user->role, ['super_admin', 'manager', 'asisten_manager'])) {
                            return true;
                        }
                        
                        // Technician hanya bisa lihat kalau dia di-assign
                        if ($user->role === 'technician') {
                            return $record->technicians->contains($user->id);
                        }
                        
                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'investigating',
                            'acknowledged_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Trouble investigation started')
                            ->send();
                    }),
                
                // Start Progress (Investigating -> In Progress)
                Action::make('start_progress')
                    ->label('Start Work')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(function ($record) {
                        if ($record->status !== 'investigating') {
                            return false;
                        }
                        
                        $user = Auth::user();
                        
                        // Manager dan asisten_manager selalu bisa lihat
                        if (in_array($user->role, ['super_admin', 'manager', 'asisten_manager'])) {
                            return true;
                        }
                        
                        // Technician hanya bisa lihat kalau dia di-assign
                        if ($user->role === 'technician') {
                            return $record->technicians->contains($user->id);
                        }
                        
                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'in_progress',
                            'started_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Work started')
                            ->send();
                    }),
                
                // Mark as Resolved (In Progress -> Resolved)
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record) {
                        if ($record->status !== 'in_progress') {
                            return false;
                        }
                        
                        $user = Auth::user();
                        
                        // Manager dan asisten_manager selalu bisa lihat
                        if (in_array($user->role, ['super_admin', 'manager', 'asisten_manager'])) {
                            return true;
                        }
                        
                        // Technician hanya bisa lihat kalau dia di-assign
                        if ($user->role === 'technician') {
                            return $record->technicians->contains($user->id);
                        }
                        
                        return false;
                    })
                    ->schema([
                        RichEditor::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required()
                            ->placeholder('Describe what was done to fix the issue'),
                    ])
                    ->action(function ($record, array $data) {
                        // Calculate downtime from started_at to now (resolved time)
                        $downtimeMinutes = 0;
                        if ($record->started_at) {
                            $downtimeMinutes = abs($record->started_at->diffInMinutes(now()));
                        }
                        
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                            'resolution_notes' => $data['resolution_notes'],
                            'downtime_minutes' => $downtimeMinutes,
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Trouble resolved')
                            ->body('Equipment is back online')
                            ->send();
                    }),
                
                // Close (Resolved -> Closed) - Only Manager/Assistant Manager
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === 'resolved' && 
                              in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager']))
                    ->requiresConfirmation()
                    ->modalHeading('Close Equipment Trouble')
                    ->modalDescription('Confirm that the equipment is operating normally and this issue is completely resolved.')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'closed',
                            'closed_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Trouble closed')
                            ->send();
                    }),
                
                ViewAction::make(),
                
                EditAction::make()
                    ->visible(fn () => in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])),
                ])
                ->visible(fn () => in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])),
            ]);
    }
}
