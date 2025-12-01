<?php

namespace App\Filament\Widgets;

use App\Models\Chiller2Checklist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class Chiller2TableWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Chiller 2 - Recent Checklists (Last 7 Days)')
            ->query(
                Chiller2Checklist::query()
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
                
                Tables\Columns\TextColumn::make('sat_evap_t')
                    ->label('Evap Temp (°C)')
                    ->sortable()
                    ->color(fn ($state) => $state > 10 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('sat_dis_t')
                    ->label('Discharge Temp (°C)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('evap_p')
                    ->label('Evap Press (Bar)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('conds_p')
                    ->label('Cond Press (Bar)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('motor_amps')
                    ->label('Motor Amps')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('loading')
                    ->label('FLA Loading %')
                    ->state(function (Chiller2Checklist $record): string {
                        if ($record->fla > 0) {
                            $loading = ($record->lcl / $record->fla) * 100;
                            return number_format($loading, 1) . '%';
                        }
                        return 'N/A';
                    })
                    ->color(function (Chiller2Checklist $record): string {
                        if ($record->fla > 0) {
                            $loading = ($record->lcl / $record->fla) * 100;
                            if ($loading >= 40 && $loading <= 90) return 'success';
                            if ($loading >= 30 && $loading <= 95) return 'warning';
                        }
                        return 'danger';
                    })
                    ->badge(),
                
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
