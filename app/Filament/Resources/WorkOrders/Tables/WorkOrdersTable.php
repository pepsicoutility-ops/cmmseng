<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use App\Models\Part;
use App\Models\RootCauseAnalysis;
use Filament\Forms\Components\TextInput;
use App\Services\WoService;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use app\models\User;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('wo_number')
                    ->label('WO Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('operator_name')
                    ->label('Operator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shift')
                    ->badge()
                    ->colors([
                        'primary' => '1',
                        'success' => '2',
                        'warning' => '3',
                    ])
                    ->formatStateUsing(fn (string $state): string => "Shift {$state}"),
                TextColumn::make('problem_type')
                    ->label('Problem Type')
                    ->badge()
                    ->colors([
                        'danger' => 'breakdown',
                        'warning' => 'abnormality',
                        'primary' => 'request_consumable',
                        'success' => 'improvement',
                        'info' => 'inspection',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('asset.name')
                    ->label('Machine')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assign_to')
                    ->label('Category')
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
                        'gray' => ['submitted', 'closed'],
                        'info' => 'reviewed',
                        'primary' => 'approved',
                        'warning' => 'in_progress',
                        'danger' => 'on_hold',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('total_downtime')
                    ->label('Downtime')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('mttr')
                    ->label('MTTR')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('rca_status')
                    ->label('RCA')
                    ->badge()
                    ->colors([
                        'gray' => 'not_required',
                        'danger' => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            'not_required' => '—',
                            'pending' => 'Required',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            default => '—',
                        };
                    })
                    ->tooltip(function ($record): ?string {
                        if ($record->rca_required && $record->rca_status === 'pending') {
                            return 'RCA is required for this work order (downtime > 10 min)';
                        }
                        return null;
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'in_progress' => 'In Progress',
                        'on_hold' => 'On Hold',
                        'completed' => 'Completed',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->multiple(),
                SelectFilter::make('assign_to')
                    ->label('Assigned To')
                    ->options([
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                    ])
                    ->multiple(),
                SelectFilter::make('problem_type')
                    ->label('Problem Type')
                    ->options([
                        'abnormality' => 'Abnormality',
                        'breakdown' => 'Breakdown',
                        'request_consumable' => 'Request Consumable',
                        'improvement' => 'Improvement',
                        'inspection' => 'Inspection',
                    ])
                    ->multiple(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['submitted', 'reviewed'])),
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'reviewed',
                            'reviewed_at' => now(),
                        ]);
                        
                        $record->processes()->create([
                            'action' => 'review',
                            'performed_by_gpid' => Auth::user()->gpid,
                            'timestamp' => now(),
                            'notes' => 'Work order reviewed',
                        ]);
                    })
                    ->visible(fn ($record) => $record->status === 'submitted' && in_array(Auth::user()->role, ['technician', 'asisten_manager'])),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]);
                        
                        $record->processes()->create([
                            'action' => 'approve',
                            'performed_by_gpid' => Auth::user()->gpid,
                            'timestamp' => now(),
                            'notes' => 'Work order approved',
                        ]);
                    })
                    ->visible(fn ($record) => $record->status === 'reviewed' && in_array(Auth::user()->role, ['technician', 'asisten_manager', 'manager'])),
                Action::make('start')
                    ->label('Start Work')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'in_progress',
                            'started_at' => now(),
                        ]);
                        
                        $record->processes()->create([
                            'action' => 'start',
                            'performed_by_gpid' => Auth::user()->gpid,
                            'timestamp' => now(),
                            'notes' => 'Work started',
                        ]);
                    })
                    ->visible(fn ($record) => $record->status === 'approved' && Auth::user()->role === 'technician'),
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->schema([
                        Textarea::make('completion_notes')
                            ->label('Solution/Notes')
                            ->required()
                            ->rows(3),
                        FileUpload::make('completion_photos')
                            ->label('Result Photos')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('work-orders')
                            ->disk('public')
                            ->visibility('public'),
                        Repeater::make('parts_usage')
                            ->label('Parts Used')
                            ->schema([
                                Select::make('part_id')
                                    ->label('Part')
                                    ->options(Part::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $part = Part::find($state);
                                            if ($part) {
                                                $set('unit_price', $part->unit_price);
                                            }
                                        }
                                    }),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $quantity = (int) $state;
                                        $unitPrice = (float) $get('unit_price') ?? 0;
                                        $set('cost', $quantity * $unitPrice);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('cost')
                                    ->label('Total Cost')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Part')
                            ->collapsible(),
                    ])
                    ->action(function ($record, array $data) {
                        // Use WoService to handle completion
                        $woService = app(WoService::class);
                        
                        // Set completed_at before calling service
                        $record->completed_at = now();
                        
                        // Merge completion photos with existing photos
                        if (!empty($data['completion_photos'])) {
                            $existingPhotos = $record->photos ?? [];
                            $allPhotos = array_merge($existingPhotos, $data['completion_photos']);
                            $record->photos = $allPhotos;
                            $record->save();
                        }
                        
                        // Add completion notes to process
                        $record->processes()->create([
                            'action' => 'complete',
                            'performed_by_gpid' => Auth::user()->gpid,
                            'timestamp' => $record->completed_at,
                            'notes' => $data['completion_notes'],
                        ]);
                        
                        // Complete work order with parts and calculations
                        $woService->completeWorkOrder($record, $data);
                        
                        Notification::make()
                            ->title('Work Order Completed')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'in_progress' && Auth::user()->role === 'technician'),
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(function ($record) {
                        if ($record->rca_required && $record->rca_status !== 'completed') {
                            return 'Warning: This work order requires RCA completion before closing. RCA Status: ' . ucfirst(str_replace('_', ' ', $record->rca_status));
                        }
                        return 'Are you sure you want to close this work order?';
                    })
                    ->action(function ($record) {
                        // Check if RCA is required and not completed
                        if ($record->rca_required && $record->rca_status !== 'completed') {
                            Notification::make()
                                ->title('Cannot Close Work Order')
                                ->body('RCA must be completed before closing this work order. Downtime exceeded 10 minutes.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $record->update([
                            'status' => 'closed',
                            'closed_at' => now(),
                        ]);
                        
                        $record->processes()->create([
                            'action' => 'close',
                            'performed_by_gpid' => Auth::user()->gpid,
                            'timestamp' => now(),
                            'notes' => 'Work order closed',
                        ]);
                        
                        Notification::make()
                            ->title('Work Order Closed')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'completed' && in_array(Auth::user()->role, ['asisten_manager', 'manager', 'super_admin'])),
                Action::make('create_rca')
                    ->label('Create RCA')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('danger')
                    ->url(fn ($record) => '/pep/root-cause-analyses/create?work_order_id=' . $record->id)
                    ->visible(fn ($record) => $record->rca_required && in_array($record->rca_status, ['pending', 'in_progress']) && in_array(Auth::user()->role, ['technician', 'asisten_manager', 'manager', 'super_admin'])),
                Action::make('view_rca')
                    ->label('View RCA')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(function ($record) {
                        $rca = RootCauseAnalysis::where('work_order_id', $record->id)->first();
                        return $rca ? '/pep/root-cause-analyses/' . $rca->id : null;
                    })
                    ->visible(fn ($record) => $record->rca_status === 'completed' || ($record->rca_required && RootCauseAnalysis::where('work_order_id', $record->id)->exists())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->role !== 'operator'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->role !== 'operator'),
                    RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()->role !== 'operator'),
                ])
                ->visible(fn () => !in_array(Auth::user()->role, ['operator', 'technician'])),
            ]);
    }
}
