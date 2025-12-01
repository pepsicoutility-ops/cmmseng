<?php

namespace App\Filament\Resources\PmCompliances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PmCompliancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label('Period')
                    ->badge()
                    ->colors([
                        'primary' => 'week',
                        'success' => 'month',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('period_start')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('period_end')
                    ->label('End Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('total_pm')
                    ->label('Total PM')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completed_pm')
                    ->label('Completed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overdue_pm')
                    ->label('Overdue')
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('compliance_percentage')
                    ->label('Compliance %')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state >= 95, // Green
                        'warning' => fn ($state) => $state >= 85 && $state < 95, // Yellow
                        'danger' => fn ($state) => $state < 85, // Red
                    ])
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2) . '%')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('period_end', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('period')
                    ->options([
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                    ]),
                \Filament\Tables\Filters\Filter::make('period_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->where('period_start', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->where('period_end', '<=', $date));
                    }),
            ])
            ->recordActions([
                // No edit action - read-only
            ])
            ->toolbarActions([
                // No bulk actions - read-only
            ]);
    }
}
