<?php

namespace App\Filament\Resources\Abnormalities\Tables;

use App\Filament\Resources\Abnormalities\AbnormalityResource;
use App\Models\Abnormality;
use App\Models\Area;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbnormalitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('abnormality_no')
                    ->label('No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap(),

                TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('reporter.name')
                    ->label('Reported By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null),

                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->default('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'assigned' => 'warning',
                        'in_progress' => 'info',
                        'fixed' => 'primary',
                        'verified' => 'success',
                        'closed' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => Abnormality::getStatuses()[$state] ?? $state),

                TextColumn::make('found_date')
                    ->label('Found Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('severity')
                    ->options(Abnormality::getSeverities()),

                SelectFilter::make('status')
                    ->options(Abnormality::getStatuses()),

                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(Area::pluck('name', 'id')),

                Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query): Builder => $query->overdue()),

                Filter::make('found_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('found_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('found_date', '<=', $date),
                            )
                    ),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                // Assign Action
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn ($record) => $record->canAssign())
                    ->form([
                        Select::make('assigned_to')
                            ->label('Assign To')
                            ->options(
                                User::whereIn('role', ['technician', 'senior_technician', 'supervisor'])
                                    ->whereIn('department', ['utility', 'mechanic', 'electric'])
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [$user->gpid => "{$user->name} ({$user->department})"])
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(fn ($record, array $data) => $record->assign($data['assigned_to']))
                    ->requiresConfirmation(),

                // Start Progress Action
                Action::make('start_progress')
                    ->label('Start Progress')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->canStartProgress())
                    ->action(fn ($record) => $record->startProgress())
                    ->requiresConfirmation(),

                // Mark Fixed Action
                Action::make('mark_fixed')
                    ->label('Mark Fixed')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->visible(fn ($record) => $record->canMarkFixed())
                    ->form([
                        Textarea::make('fix_description')
                            ->label('Fix Description')
                            ->required()
                            ->rows(3),
                        FileUpload::make('fix_photo')
                            ->label('Fix Evidence Photo')
                            ->image()
                            ->disk('public')
                            ->directory('abnormalities/fixes')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markFixed($data['fix_description'], $data['fix_photo'] ?? null);
                    })
                    ->requiresConfirmation(),

                // Verify Action
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->canVerify() && AbnormalityResource::userCanVerify())
                    ->form([
                        Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->rows(2),
                    ])
                    ->action(fn ($record, array $data) => $record->verify($data['verification_notes'] ?? null))
                    ->requiresConfirmation(),

                // Close Action
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(fn ($record) => $record->canClose() && AbnormalityResource::userCanVerify())
                    ->action(fn ($record) => $record->close())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => AbnormalityResource::userCanVerify()),
                ]),
            ]);
    }
}
