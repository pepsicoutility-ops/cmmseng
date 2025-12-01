<?php

namespace App\Filament\Widgets;

use App\Models\Compressor1Checklist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class Compressor1TableWidget extends BaseWidget
{
    protected static ?int $sort = 8;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'super_admin' ||
            $user->department === 'utility'
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Compressor 1 - Recent Checklists (Last 7 Days)')
            ->query(
                Compressor1Checklist::query()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'info',
                        2 => 'warning',
                        3 => 'success',
                        default => 'gray'
                    }),
                
                Tables\Columns\TextColumn::make('bearing_oil_temperature')
                    ->label('Oil Temp (°C)')
                    ->sortable()
                    ->color(fn ($state) => $state > 60 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('bearing_oil_pressure')
                    ->label('Oil Press (Bar)')
                    ->sortable()
                    ->color(fn ($state) => $state < 1.5 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('discharge_pressure')
                    ->label('Discharge Press (Bar)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('discharge_temperature')
                    ->label('Discharge Temp (°C)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('cooling_delta_t')
                    ->label('Cooling ΔT (°C)')
                    ->state(function (Compressor1Checklist $record): string {
                        $delta = $record->cws_temperature - $record->cwr_temperature;
                        return number_format($delta, 2);
                    })
                    ->color(function (Compressor1Checklist $record): string {
                        $delta = $record->cws_temperature - $record->cwr_temperature;
                        return $delta < 3 ? 'warning' : 'success';
                    }),
                
                Tables\Columns\TextColumn::make('refrigerant_pressure')
                    ->label('Ref Press (Bar)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('dew_point')
                    ->label('Dew Point (°C)')
                    ->sortable()
                    ->color(fn ($state) => $state > 5 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('gpid')
                    ->label('Created By')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->striped();
    }
}
