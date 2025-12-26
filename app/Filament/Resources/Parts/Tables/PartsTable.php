<?php

namespace App\Filament\Resources\Parts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('name')
                    ->label('Part Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'electric' => 'warning',
                        'mechanic' => 'info',
                        'consumable' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->label('Total Stock')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => ' ' . $record->unit)
                    ->badge()
                    ->color(fn ($record): string => 
                        $record->current_stock == 0 ? 'danger' :
                        ($record->current_stock <= $record->min_stock ? 'warning' : 'success')
                    )
                    ->description(fn ($record) => $record->inventories_count > 0 
                        ? "Stored in {$record->inventories_count} location(s)"
                        : null
                    ),
                TextColumn::make('min_stock')
                    ->label('Min')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => ' ' . $record->unit)
                    ->toggleable(),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record): string => 
                        $record->current_stock == 0 ? 'OUT OF STOCK' :
                        ($record->current_stock <= $record->min_stock ? 'LOW STOCK' : 'SUFFICIENT')
                    )
                    ->color(fn ($record): string => 
                        $record->current_stock == 0 ? 'danger' :
                        ($record->current_stock <= $record->min_stock ? 'warning' : 'success')
                    ),
                TextColumn::make('unit_price')
                    ->label('Price')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'electric' => 'Electric',
                        'mechanic' => 'Mechanic',
                        'consumable' => 'Consumable',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'out' => 'Out of Stock',
                        'low' => 'Low Stock',
                        'sufficient' => 'Sufficient',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'out') {
                            return $query->where('current_stock', 0);
                        }
                        if ($state['value'] === 'low') {
                            return $query->whereColumn('current_stock', '<=', 'min_stock')
                                ->where('current_stock', '>', 0);
                        }
                        if ($state['value'] === 'sufficient') {
                            return $query->whereColumn('current_stock', '>', 'min_stock');
                        }
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
