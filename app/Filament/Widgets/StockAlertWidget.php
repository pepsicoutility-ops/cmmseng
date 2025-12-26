<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use App\Models\Part;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockAlertWidget extends TableWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager','tech_store']);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::query()
                    ->whereColumn('current_stock', '<', 'min_stock')
                    ->orderBy('current_stock', 'asc')
            )
            ->heading('Low Stock Alert')
            ->columns([
                TextColumn::make('part_number')
                    ->label('Part Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Part Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Min Stock')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('current_stock', 'asc')
            ->paginated([5, 10, 25]);
    }
}
