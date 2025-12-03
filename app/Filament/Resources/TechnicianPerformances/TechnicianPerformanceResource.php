<?php

namespace App\Filament\Resources\TechnicianPerformances;

use App\Filament\Resources\TechnicianPerformances\Pages\ListTechnicianPerformances;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TechnicianPerformanceResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $navigationLabel = 'Technician Performance';
    
    protected static UnitEnum|string|null $navigationGroup = 'Reports & Analytics';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static ?int $navigationSort = 4;
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    protected static function calculateScore($record): int
    {
        $score = 0;
        $total = $record->total_pm ?? 0;
        $onTime = $record->pm_on_time ?? 0;
        $totalWo = $record->total_wo ?? 0;
        
        // PM Compliance (40 points)
        if ($total > 0) {
            $compliance = ($onTime / $total) * 100;
            $score += ($compliance / 100) * 40;
        }
        
        // Work load (30 points)
        $totalWork = $total + $totalWo;
        if ($totalWork >= 20) {
            $score += 30;
        } elseif ($totalWork >= 10) {
            $score += 20;
        } elseif ($totalWork >= 5) {
            $score += 10;
        }
        
        // Activity (30 points)
        if ($total > 0 || $totalWo > 0) {
            $score += 30;
        }
        
        return (int) round($score);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('role', 'technician')
            ->select([
                'users.*',
                DB::raw('(SELECT COUNT(*) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed") as total_pm'),
                DB::raw('(SELECT COUNT(*) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed" AND is_on_time = 1) as pm_on_time'),
                DB::raw('(SELECT AVG(duration) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed") as avg_pm_duration'),
                DB::raw('(SELECT COUNT(DISTINCT wo.id) FROM wo_processes wp JOIN work_orders wo ON wp.work_order_id = wo.id WHERE wp.performed_by_gpid = users.gpid AND wp.action = "complete" AND wo.status IN ("completed", "closed")) as total_wo'),
                DB::raw('(SELECT AVG(wo.mttr) FROM wo_processes wp JOIN work_orders wo ON wp.work_order_id = wo.id WHERE wp.performed_by_gpid = users.gpid AND wp.action = "complete" AND wo.status IN ("completed", "closed")) as avg_mttr'),
            ]);
        
        // Filter by department for asisten_manager
        $user = Auth::user();
        if ($user && $user->role === 'asisten_manager') {
            $query->where('department', $user->department);
        }
        
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gpid')
                    ->label('GPID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Technician Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('department')
                    ->badge()
                    ->colors([
                        'success' => 'utility',
                        'warning' => 'electric',
                        'danger' => 'mechanic',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                TextColumn::make('total_pm')
                    ->label('Total PM')
                    ->sortable()
                    ->alignCenter()
                    ->default(0),
                TextColumn::make('pm_on_time')
                    ->label('PM On-Time')
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->color('success'),
                TextColumn::make('pm_compliance')
                    ->label('PM Compliance')
                    ->state(function ($record) {
                        $total = $record->total_pm ?? 0;
                        $onTime = $record->pm_on_time ?? 0;
                        if ($total == 0) return 'N/A';
                        $percentage = ($onTime / $total) * 100;
                        return number_format($percentage, 1) . '%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $total = $record->total_pm ?? 0;
                        $onTime = $record->pm_on_time ?? 0;
                        if ($total == 0) return 'gray';
                        $percentage = ($onTime / $total) * 100;
                        if ($percentage >= 95) return 'success';
                        if ($percentage >= 85) return 'warning';
                        return 'danger';
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('avg_pm_duration')
                    ->label('Avg PM Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' min' : '-')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                TextColumn::make('total_wo')
                    ->label('Total WO')
                    ->sortable()
                    ->alignCenter()
                    ->default(0),
                TextColumn::make('avg_mttr')
                    ->label('Avg MTTR')
                    ->formatStateUsing(fn ($state) => $state ? number_format(abs($state), 1) . ' min' : '-')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn ($state) => $state && $state > 0 ? 'Response time' : null)
                    ->toggleable(),
                TextColumn::make('performance_score')
                    ->label('Score')
                    ->getStateUsing(function ($record) {
                        return static::calculateScore($record);
                    })
                    ->badge()
                    ->color(function ($record) {
                        $score = static::calculateScore($record);
                        if ($score >= 90) return 'success';
                        if ($score >= 70) return 'warning';
                        return 'danger';
                    })
                    ->alignCenter()
                    ->weight('bold')
                    ->sortable(false),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->options([
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                    ])
                    ->visible(fn () => Auth::user()->role !== 'asisten_manager'),
            ])
            ->defaultSort('name', 'asc')
            ->recordActions([])
            ->bulkActions([]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ListTechnicianPerformances::route('/'),
        ];
    }
}
