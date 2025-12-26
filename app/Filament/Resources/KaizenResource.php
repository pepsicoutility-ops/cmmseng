<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaizenResource\Pages;
use App\Filament\Resources\KaizenResource\Schemas\KaizenForm;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\Kaizen;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class KaizenResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = Kaizen::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $navigationLabel = 'Kaizen';

    protected static string | UnitEnum | null $navigationGroup = 'Performance KPIs';

    protected static ?int $navigationSort = 1;

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }

    public static function form(Schema $schema): Schema
    {
        return KaizenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Kaizen ID')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => 'KZ-' . str_pad($record->id, 5, '0', STR_PAD_LEFT)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('submittedBy.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department')
                    ->label('Department')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'utility' => 'info',
                        'mechanic' => 'warning',
                        'electric' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '-')),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RECON' => 'success',
                        'DT_REDUCTION' => 'warning',
                        'SAFETY_QUALITY' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'RECON' => 'RECON',
                        'DT_REDUCTION' => 'DT Reduction',
                        'SAFETY_QUALITY' => 'Safety & Quality',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state, $record) => $record->status === Kaizen::STATUS_CLOSED ? $state : '-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => Kaizen::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => Kaizen::getStatuses()[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost_saved')
                    ->label('Cost Saved')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->options([
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                    ])
                    ->visible(fn () => self::userCanViewAllDepartments()),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'RECON' => 'RECON',
                        'DT_REDUCTION' => 'DT Reduction',
                        'SAFETY_QUALITY' => 'Safety & Quality',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options(Kaizen::getStatuses()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            )
                    ),
            ])
            ->actions([
                // View action - always visible
                Actions\ViewAction::make(),

                // Edit action - only for own submitted kaizen or reviewer
                Actions\EditAction::make()
                    ->visible(function (Kaizen $record): bool {
                        $user = Auth::user();
                        if (!$user instanceof User) return false;
                        
                        // Owner can edit only if still submitted
                        if ($record->submitted_by_gpid === $user->gpid && $record->status === Kaizen::STATUS_SUBMITTED) {
                            return true;
                        }
                        
                        return false;
                    }),

                // === ASISTEN MANAGER ACTIONS ===
                
                // Review action (AM only) - mark as under review
                Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Start Review')
                    ->modalDescription('Mark this Kaizen as under review?')
                    ->action(fn (Kaizen $record) => $record->update([
                        'status' => Kaizen::STATUS_UNDER_REVIEW,
                        'reviewed_by_gpid' => Auth::user()?->gpid,
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeReviewed() && self::userCanReview($record)
                    ),

                // Approve action (AM only)
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Kaizen')
                    ->modalDescription('Approve this Kaizen for execution?')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Approval Notes (Optional)')
                            ->rows(2),
                    ])
                    ->action(fn (Kaizen $record, array $data) => $record->update([
                        'status' => Kaizen::STATUS_APPROVED,
                        'reviewed_by_gpid' => Auth::user()?->gpid,
                        'review_notes' => $data['review_notes'] ?? $record->review_notes,
                        'approved_at' => now(),
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeApproved() && self::userCanReview($record)
                    ),

                // Reject action (AM only)
                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Kaizen')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn (Kaizen $record, array $data) => $record->update([
                        'status' => Kaizen::STATUS_REJECTED,
                        'reviewed_by_gpid' => Auth::user()?->gpid,
                        'review_notes' => $data['review_notes'],
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeRejected() && self::userCanReview($record)
                    ),

                // Close action (AM only)
                Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-badge')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Close Kaizen')
                    ->modalDescription('Close this completed Kaizen?')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Closing Notes (Optional)')
                            ->rows(2),
                    ])
                    ->action(fn (Kaizen $record, array $data) => $record->update([
                        'status' => Kaizen::STATUS_CLOSED,
                        'closed_by_gpid' => Auth::user()?->gpid,
                        'closed_at' => now(),
                        'review_notes' => $data['review_notes'] ?? $record->review_notes,
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeClosed() && self::userCanReview($record)
                    ),

                // === TECHNICIAN ACTIONS ===
                
                // Start Execution (Technician - owner only)
                Actions\Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Start Kaizen Execution')
                    ->modalDescription('Start working on this Kaizen?')
                    ->action(fn (Kaizen $record) => $record->update([
                        'status' => Kaizen::STATUS_IN_PROGRESS,
                        'started_at' => now(),
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeStarted() && self::userIsOwner($record)
                    ),

                // Complete Execution (Technician - owner only)
                Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Kaizen')
                    ->form([
                        Forms\Components\Textarea::make('completion_notes')
                            ->label('Completion Notes')
                            ->rows(3)
                            ->placeholder('Describe what was done and the results achieved'),
                        Forms\Components\TextInput::make('cost_saved')
                            ->label('Actual Cost Saved (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0'),
                        Forms\Components\DatePicker::make('implementation_date')
                            ->label('Implementation Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(fn (Kaizen $record, array $data) => $record->update([
                        'status' => Kaizen::STATUS_COMPLETED,
                        'completed_at' => now(),
                        'completion_notes' => $data['completion_notes'],
                        'cost_saved' => $data['cost_saved'] ?? $record->cost_saved,
                        'implementation_date' => $data['implementation_date'],
                    ]))
                    ->visible(fn (Kaizen $record): bool => 
                        $record->canBeCompleted() && self::userIsOwner($record)
                    ),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->visible(fn () => self::userIsSuperAdmin()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKaizens::route('/'),
            'create' => Pages\CreateKaizen::route('/create'),
            'view' => Pages\ViewKaizen::route('/{record}'),
            'edit' => Pages\EditKaizen::route('/{record}/edit'),
        ];
    }

    /**
     * Filter query based on user role and department
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        // Super Admin & Manager can see all
        if (in_array($user->role, ['super_admin', 'manager'])) {
            return $query;
        }

        // Asisten Manager can only see their department's kaizens
        if ($user->role === 'asisten_manager') {
            return $query->where('department', $user->department);
        }

        // Technician/Engineer/Supervisor can only see their own kaizens
        return $query->where('submitted_by_gpid', $user->gpid);
    }

    /**
     * Check if current user can create kaizen
     */
    protected static function userCanCreate(): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;
        
        // Technicians, Engineers, Supervisors can create
        return in_array($user->role, ['technician', 'engineer', 'supervisor', 'asisten_manager', 'manager', 'super_admin']);
    }

    /**
     * Check if current user can review (AM of same department, Manager, Super Admin)
     */
    protected static function userCanReview(Kaizen $record): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;

        // Super Admin & Manager can review all
        if (in_array($user->role, ['super_admin', 'manager'])) {
            return true;
        }

        // Asisten Manager can only review their department's kaizens
        if ($user->role === 'asisten_manager' && $user->department === $record->department) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user is the owner of kaizen
     */
    protected static function userIsOwner(Kaizen $record): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;

        return $record->submitted_by_gpid === $user->gpid;
    }

    /**
     * Check if user can view all departments
     */
    protected static function userCanViewAllDepartments(): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;

        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Check if user is super admin
     */
    protected static function userIsSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;

        return $user->role === 'super_admin';
    }
}
