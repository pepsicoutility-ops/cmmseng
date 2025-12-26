<?php

namespace App\Filament\Resources\InventoryReports;

use Maatwebsite\Excel\Excel;
use App\Filament\Resources\InventoryReports\Pages\ManageInventoryReports;
use App\Models\Part;
use UnitEnum;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Traits\HasRoleBasedAccess;

class InventoryReportResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = Part::class;
    
    protected static ?string $navigationLabel = 'Inventory Reports';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Reports & Analytics';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCubeTransparent;
    
    protected static ?int $navigationSort = 3;

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Part Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->current_stock < $record->min_stock ? 'danger' : 'success'),
                TextColumn::make('min_stock')
                    ->label('Min Stock')
                    ->sortable(),
                TextColumn::make('unit')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('stock_value')
                    ->label('Stock Value')
                    ->money('IDR')
                    ->state(fn ($record) => $record->current_stock * $record->unit_price),
                TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->current_stock == 0) return 'out_of_stock';
                        if ($record->current_stock < $record->min_stock) return 'low_stock';
                        return 'adequate';
                    })
                    ->colors([
                        'danger' => 'out_of_stock',
                        'warning' => 'low_stock',
                        'success' => 'adequate',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'consumable' => 'Consumable',
                        'spare_part' => 'Spare Part',
                        'tool' => 'Tool',
                        'chemical' => 'Chemical',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                Filter::make('low_stock')
                    ->label('Low Stock Only')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('current_stock', '<', 'min_stock')),
                Filter::make('out_of_stock')
                    ->label('Out of Stock Only')
                    ->query(fn (Builder $query): Builder => $query->where('current_stock', 0)),
            ])
            ->defaultSort('current_stock', 'asc')
            ->recordActions([])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('Inventory_Report_' . date('Y-m-d_His'))
                            ->withWriterType(Excel::XLSX),
                    ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('Inventory_Report_Selected_' . date('Y-m-d_His'))
                            ->withWriterType(Excel::XLSX),
                    ]),
            ]);
    }
    
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryReports::route('/'),
        ];
    }
}
