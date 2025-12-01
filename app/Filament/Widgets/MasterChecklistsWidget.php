<?php

namespace App\Filament\Widgets;

use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use App\Models\AhuChecklist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MasterChecklistsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Checklists - Last 7 Days')
            ->query(
                Chiller1Checklist::query()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(fn ($state) => 'C1-' . $state)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('equipment')
                    ->label('Equipment')
                    ->default('Chiller 1')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('sat_evap_t')
                    ->label('Evap Temp')
                    ->suffix('°C')
                    ->default('-'),
                Tables\Columns\TextColumn::make('sat_dis_t')
                    ->label('Discharge Temp')
                    ->suffix('°C')
                    ->default('-'),
                Tables\Columns\TextColumn::make('oil_p')
                    ->label('Oil Pressure')
                    ->suffix(' Bar')
                    ->default('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->default('Normal')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('gpid')
                    ->label('Created By')
                    ->default('N/A'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }
}
