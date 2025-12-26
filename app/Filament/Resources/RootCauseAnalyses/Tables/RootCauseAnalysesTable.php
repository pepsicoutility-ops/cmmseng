<?php

namespace App\Filament\Resources\RootCauseAnalyses\Tables;

use App\Models\RootCauseAnalysis;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RootCauseAnalysesTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rca_number')
                    ->label('RCA No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('workOrder.wo_number')
                    ->label('Work Order')
                    ->searchable()
                    ->url(fn ($record) => '/pep/work-orders/' . $record->work_order_id),

                Tables\Columns\TextColumn::make('workOrder.subAsset.name')
                    ->label('Equipment')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('workOrder.total_downtime')
                    ->label('Downtime')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('root_cause_category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '-')),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'reviewed' => 'warning',
                        'approved' => 'success',
                        'closed' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('action_deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->action_deadline && $record->action_deadline < now() && !in_array($record->status, ['approved', 'closed']) ? 'danger' : null),

                Tables\Columns\IconColumn::make('ai_assisted')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-minus'),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('root_cause_category')
                    ->label('Category')
                    ->options([
                        'man' => 'Man',
                        'machine' => 'Machine',
                        'method' => 'Method',
                        'material' => 'Material',
                        'measurement' => 'Measurement',
                        'environment' => 'Environment',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Actions')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\Filter::make('ai_assisted')
                    ->label('AI Assisted')
                    ->query(fn (Builder $query) => $query->where('ai_assisted', true)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (RootCauseAnalysis $record) => $record->isEditable()),

                // Workflow Actions
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (RootCauseAnalysis $record) => $record->canBeSubmitted())
                    ->requiresConfirmation()
                    ->action(function (RootCauseAnalysis $record) {
                        $record->submit();
                        Notification::make()->title('RCA submitted for review')->success()->send();
                    }),

                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(function (RootCauseAnalysis $record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $record->canBeReviewed() && $user && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);
                    })
                    ->requiresConfirmation()
                    ->action(function (RootCauseAnalysis $record) {
                        $record->review(Auth::user()->gpid);
                        Notification::make()->title('RCA reviewed')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (RootCauseAnalysis $record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $record->canBeApproved() && $user && in_array($user->role, ['manager', 'super_admin']);
                    })
                    ->requiresConfirmation()
                    ->action(function (RootCauseAnalysis $record) {
                        $record->approve(Auth::user()->gpid);
                        Notification::make()->title('RCA approved')->success()->send();
                    }),

                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('primary')
                    ->visible(function (RootCauseAnalysis $record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $record->canBeClosed() && $user && in_array($user->role, ['manager', 'super_admin']);
                    })
                    ->requiresConfirmation()
                    ->action(function (RootCauseAnalysis $record) {
                        $record->close();
                        Notification::make()->title('RCA closed')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (RootCauseAnalysis $record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return in_array($record->status, ['submitted', 'reviewed']) && $user && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);
                    })
                    ->requiresConfirmation()
                    ->action(function (RootCauseAnalysis $record) {
                        $record->rejectToSubmitted();
                        Notification::make()->title('RCA rejected - returned to submitter')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function () {
                            /** @var User|null $user */
                            $user = Auth::user();
                            return $user && in_array($user->role, ['super_admin', 'manager']);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
