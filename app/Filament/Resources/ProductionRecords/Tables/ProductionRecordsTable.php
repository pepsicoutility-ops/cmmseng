<?php

namespace App\Filament\Resources\ProductionRecords\Tables;

use App\Models\ProductionRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ProductionRecordsTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('record_no')
                    ->label('Record No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('production_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift_label')
                    ->label('Shift')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subArea.name')
                    ->label('Line')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('weight_kg')
                    ->label('Total (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('good_product_kg')
                    ->label('Good (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->color('success'),

                Tables\Columns\TextColumn::make('waste_kg')
                    ->label('Waste (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('yield_percentage')
                    ->label('Yield %')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->alignEnd()
                    ->color(fn ($state) => $state >= 95 ? 'success' : ($state >= 90 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'verified' => 'info',
                        'approved' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),

                Tables\Filters\SelectFilter::make('shift')
                    ->options([
                        1 => 'Shift 1',
                        2 => 'Shift 2',
                        3 => 'Shift 3',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'verified' => 'Verified',
                        'approved' => 'Approved',
                    ]),

                Tables\Filters\Filter::make('production_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('production_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('production_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (ProductionRecord $record) => $record->status === 'draft'),

                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (ProductionRecord $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (ProductionRecord $record) {
                        $record->submit();
                        Notification::make()->title('Record submitted for verification')->success()->send();
                    }),

                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (ProductionRecord $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (ProductionRecord $record) {
                        $record->verify();
                        Notification::make()->title('Record verified')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProductionRecord $record) => $record->status === 'verified')
                    ->requiresConfirmation()
                    ->action(function (ProductionRecord $record) {
                        $record->approve();
                        Notification::make()->title('Record approved')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ProductionRecord $record) => in_array($record->status, ['submitted', 'verified']))
                    ->requiresConfirmation()
                    ->action(function (ProductionRecord $record) {
                        $record->reject();
                        Notification::make()->title('Record rejected - returned to draft')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function () {
                            /** @var \App\Models\User|null $user */
                            $user = Auth::user();
                            return $user && in_array($user->role, ['super_admin', 'manager']);
                        }),
                ]),
            ])
            ->defaultSort('production_date', 'desc');
    }
}
