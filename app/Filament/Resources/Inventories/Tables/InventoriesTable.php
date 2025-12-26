<?php

namespace App\Filament\Resources\Inventories\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\InventoryMovement;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class InventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('part.part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('part.name')
                    ->label('Part Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('part.current_stock')
                    ->label('Total Stock (All Locations)')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->description('Sum of all inventories'),
                TextColumn::make('quantity')
                    ->label('Stock at This Location')
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->quantity == 0 => 'danger',
                        $record->quantity <= $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Min (from Part)')
                    ->sortable()
                    ->description('Synced from Part'),
                TextColumn::make('max_stock')
                    ->label('Max')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match(true) {
                        $record->quantity == 0 => 'Out of Stock',
                        $record->quantity <= $record->min_stock => 'Low Stock',
                        default => 'Sufficient',
                    })
                    ->color(fn ($record) => match(true) {
                        $record->quantity == 0 => 'danger',
                        $record->quantity <= $record->min_stock => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('location')
                    ->label('Location (from Part)')
                    ->searchable()
                    ->toggleable()
                    ->description('Synced from Part'),
                TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'sufficient' => 'Sufficient',
                        'low' => 'Low Stock',
                        'out' => 'Out of Stock',
                    ])
                    ->query(function ($query, $data) {
                        return match($data['value'] ?? null) {
                            'out' => $query->whereRaw('quantity = 0'),
                            'low' => $query->whereRaw('quantity > 0 AND quantity <= min_stock'),
                            'sufficient' => $query->whereRaw('quantity > min_stock'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('addStock')
                    ->label('Add Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Quantity to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('notes')
                            ->label('Notes (Optional)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        // Update inventory quantity directly
                        $record->quantity += $data['quantity'];
                        $record->last_restocked_at = now();
                        $record->save();
                        // Model event will automatically sync Part current_stock
                        
                        // Create inventory movement record if notes provided
                        if (!empty($data['notes'])) {
                            InventoryMovement::create([
                                'part_id' => $record->part_id,
                                'movement_type' => 'addition',
                                'quantity' => $data['quantity'],
                                'reference_type' => 'manual',
                                'notes' => $data['notes'],
                                'performed_by' => Auth::id(),
                            ]);
                        }
                        
                        Notification::make()
                            ->title('Stock Added')
                            ->body($data['quantity'] . ' units added. Total stock at this location: ' . $record->quantity)
                            ->success()
                            ->send();
                    }),
                Action::make('adjustStock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->schema([
                        TextInput::make('new_quantity')
                            ->label('Set New Quantity')
                            ->helperText('Enter the new quantity for this location')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(fn ($record) => $record->quantity),
                        Textarea::make('notes')
                            ->label('Reason for Adjustment')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $oldQuantity = $record->quantity;
                        $difference = $data['new_quantity'] - $oldQuantity;
                        
                        // Update inventory quantity
                        $record->quantity = $data['new_quantity'];
                        $record->save();
                        // Model event will automatically sync Part current_stock
                        
                        // Create inventory movement record
                        InventoryMovement::create([
                            'part_id' => $record->part_id,
                            'movement_type' => $difference > 0 ? 'addition' : 'deduction',
                            'quantity' => abs($difference),
                            'reference_type' => 'adjustment',
                            'notes' => $data['notes'],
                            'performed_by' => Auth::id(),
                        ]);
                        
                        Notification::make()
                            ->title('Stock Adjusted')
                            ->body('Quantity changed from ' . $oldQuantity . ' to ' . $data['new_quantity'])
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->role === 'super_admin'),
                ]),
            ]);
    }
}
