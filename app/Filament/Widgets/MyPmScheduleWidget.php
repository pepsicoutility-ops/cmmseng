<?php

namespace App\Filament\Widgets;

use App\Models\PmExecution;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MyPmScheduleWidget extends TableWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'technician';
    }
    
    public function table(Table $table): Table
    {
        $user = Auth::user();
        
        return $table
            ->query(
                PmExecution::query()
                    ->where('executed_by_gpid', $user->gpid)
                    ->where('scheduled_date', '>=', Carbon::now()->startOfDay())
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->orderBy('scheduled_date', 'asc')
            )
            ->heading('My Upcoming PM Tasks')
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->scheduled_date->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('pmSchedule.code')
                    ->label('PM Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pmSchedule.title')
                    ->label('PM Title')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('pmSchedule.asset.asset_name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pmSchedule.asset.asset_tag')
                    ->label('Asset Tag')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                    ]),
                Tables\Columns\BadgeColumn::make('compliance_status')
                    ->label('Compliance')
                    ->colors([
                        'success' => 'on_time',
                        'warning' => 'late',
                        'danger' => 'very_late',
                    ]),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->paginated([10, 25, 50]);
    }
}
