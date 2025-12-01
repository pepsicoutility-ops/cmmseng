<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('part.part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('part.name')
                    ->label('Part Name')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->toggleable(),
                TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->toggleable(),
                TextColumn::make('performedBy.name')
                    ->label('Performed By')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'in' => 'IN',
                        'out' => 'OUT',
                        'adjustment' => 'ADJUSTMENT',
                    ]),
                SelectFilter::make('reference_type')
                    ->label('Reference Type')
                    ->options([
                        'manual' => 'Manual',
                        'pm_execution' => 'PM Execution',
                        'work_order' => 'Work Order',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
