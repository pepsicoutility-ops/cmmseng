<?php

namespace App\Filament\Resources\UtilityConsumptions\Tables;

use App\Models\UtilityConsumption;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class UtilityConsumptionsTable
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

                Tables\Columns\TextColumn::make('consumption_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift_label')
                    ->label('Shift')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->placeholder('Plant-wide'),

                Tables\Columns\TextColumn::make('water_consumption')
                    ->label('Water (L)')
                    ->numeric(2)
                    ->alignEnd()
                    ->color('info')
                    ->icon('heroicon-o-beaker'),

                Tables\Columns\TextColumn::make('electricity_consumption')
                    ->label('Electricity (kWh)')
                    ->numeric(2)
                    ->alignEnd()
                    ->color('warning')
                    ->icon('heroicon-o-bolt'),

                Tables\Columns\TextColumn::make('gas_consumption')
                    ->label('Gas (kWh)')
                    ->numeric(2)
                    ->alignEnd()
                    ->color('danger')
                    ->icon('heroicon-o-fire'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('IDR')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Filters\Filter::make('consumption_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('consumption_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('consumption_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (UtilityConsumption $record) => $record->status === 'draft'),

                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (UtilityConsumption $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (UtilityConsumption $record) {
                        $record->submit();
                        Notification::make()->title('Record submitted for verification')->success()->send();
                    }),

                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (UtilityConsumption $record) => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (UtilityConsumption $record) {
                        $record->verify();
                        Notification::make()->title('Record verified')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UtilityConsumption $record) => $record->status === 'verified')
                    ->requiresConfirmation()
                    ->action(function (UtilityConsumption $record) {
                        $record->approve();
                        Notification::make()->title('Record approved')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UtilityConsumption $record) => in_array($record->status, ['submitted', 'verified']))
                    ->requiresConfirmation()
                    ->action(function (UtilityConsumption $record) {
                        $record->reject();
                        Notification::make()->title('Record rejected - returned to draft')->warning()->send();
                    }),

                Action::make('calculate_costs')
                    ->label('Calculate Costs')
                    ->icon('heroicon-o-calculator')
                    ->color('gray')
                    ->visible(fn (UtilityConsumption $record) => $record->status === 'approved' && $record->total_cost == 0)
                    ->action(function (UtilityConsumption $record) {
                        $record->calculateCosts();
                        Notification::make()->title('Costs calculated')->success()->send();
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
            ->defaultSort('consumption_date', 'desc');
    }
}
