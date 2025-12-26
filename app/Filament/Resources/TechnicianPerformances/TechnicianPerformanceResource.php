<?php

namespace App\Filament\Resources\TechnicianPerformances;

use App\Filament\Resources\TechnicianPerformances\Pages\ListTechnicianPerformances;
use App\Filament\Traits\HasRoleBasedAccess;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TechnicianPerformanceResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = User::class;
    
    protected static ?string $navigationLabel = 'Technician Performance';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Reports & Analytics';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static ?int $navigationSort = 4;
    
    public static function canAccess(): bool
    {
        return static::canAccessManagement();
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    /**
     * Calculate performance score based on 2026 KPIs
     * 
     * Scoring breakdown (100 points total):
     * - PM Compliance (25 points): Target >90%
     * - MTTR Performance (25 points): Target <50 minutes
     * - Kaizen (25 points): Target min 4/year (prorated monthly)
     * - WO Completion (25 points): Based on total WO handled
     */
    protected static function calculateScore($record): int
    {
        $score = 0;
        
        // 1. PM Compliance Score (25 points) - Target: >90%
        $totalPm = $record->total_pm ?? 0;
        $onTimePm = $record->pm_on_time ?? 0;
        if ($totalPm > 0) {
            $pmCompliance = ($onTimePm / $totalPm) * 100;
            if ($pmCompliance >= 95) {
                $score += 25;
            } elseif ($pmCompliance >= 90) {
                $score += 20;
            } elseif ($pmCompliance >= 80) {
                $score += 15;
            } elseif ($pmCompliance >= 70) {
                $score += 10;
            } else {
                $score += 5;
            }
        }
        
        // 2. MTTR Performance Score (25 points) - Target: <50 minutes
        $avgMttr = abs($record->avg_mttr ?? 0);
        if ($avgMttr > 0) {
            if ($avgMttr <= 30) {
                $score += 25; // Excellent
            } elseif ($avgMttr <= 50) {
                $score += 20; // Target achieved
            } elseif ($avgMttr <= 60) {
                $score += 15; // Near target
            } elseif ($avgMttr <= 90) {
                $score += 10; // Needs improvement
            } else {
                $score += 5; // Poor
            }
        }
        
        // 3. Kaizen Score (25 points) - Target: 4/year = ~0.33/month
        $kaizenClosed = $record->kaizen_closed ?? 0;
        $kaizenScore = $record->kaizen_total_score ?? 0;
        if ($kaizenClosed >= 1) {
            // Monthly view: 1 kaizen/month is excellent
            $score += min(25, $kaizenClosed * 12 + ($kaizenScore > 0 ? 5 : 0));
        }
        
        // 4. WO Completion Score (25 points)
        $totalWo = $record->total_wo ?? 0;
        if ($totalWo >= 10) {
            $score += 25;
        } elseif ($totalWo >= 7) {
            $score += 20;
        } elseif ($totalWo >= 5) {
            $score += 15;
        } elseif ($totalWo >= 3) {
            $score += 10;
        } elseif ($totalWo >= 1) {
            $score += 5;
        }
        
        return min(100, (int) round($score));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('role', 'technician')
            ->select([
                'users.*',
                // PM Metrics
                DB::raw('(SELECT COUNT(*) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed") as total_pm'),
                DB::raw('(SELECT COUNT(*) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed" AND is_on_time = 1) as pm_on_time'),
                DB::raw('(SELECT AVG(duration) FROM pm_executions WHERE executed_by_gpid = users.gpid AND status = "completed") as avg_pm_duration'),
                // WO Metrics
                DB::raw('(SELECT COUNT(DISTINCT wo.id) FROM wo_processes wp JOIN work_orders wo ON wp.work_order_id = wo.id WHERE wp.performed_by_gpid = users.gpid AND wp.action = "complete" AND wo.status IN ("completed", "closed")) as total_wo'),
                DB::raw('(SELECT AVG(wo.mttr) FROM wo_processes wp JOIN work_orders wo ON wp.work_order_id = wo.id WHERE wp.performed_by_gpid = users.gpid AND wp.action = "complete" AND wo.status IN ("completed", "closed")) as avg_mttr'),
                DB::raw('(SELECT SUM(wo.total_downtime) FROM wo_processes wp JOIN work_orders wo ON wp.work_order_id = wo.id WHERE wp.performed_by_gpid = users.gpid AND wp.action = "complete" AND wo.status IN ("completed", "closed")) as total_downtime'),
                // Kaizen Metrics
                DB::raw('(SELECT COUNT(*) FROM kaizens WHERE submitted_by_gpid = users.gpid) as kaizen_submitted'),
                DB::raw('(SELECT COUNT(*) FROM kaizens WHERE submitted_by_gpid = users.gpid AND status = "closed") as kaizen_closed'),
                DB::raw('(SELECT COALESCE(SUM(score), 0) FROM kaizens WHERE submitted_by_gpid = users.gpid AND status = "closed") as kaizen_total_score'),
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
                    
                // PM Metrics Group
                TextColumn::make('total_pm')
                    ->label('PM Done')
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
                        if ($percentage >= 90) return 'success';
                        if ($percentage >= 80) return 'warning';
                        return 'danger';
                    })
                    ->sortable()
                    ->alignCenter()
                    ->description('Target: >90%'),
                    
                // WO Metrics Group
                TextColumn::make('total_wo')
                    ->label('WO Done')
                    ->sortable()
                    ->alignCenter()
                    ->default(0),
                TextColumn::make('avg_mttr')
                    ->label('Avg MTTR')
                    ->formatStateUsing(fn ($state) => $state ? number_format(abs($state), 0) . ' min' : '-')
                    ->badge()
                    ->color(function ($record) {
                        $mttr = abs($record->avg_mttr ?? 0);
                        if ($mttr == 0) return 'gray';
                        if ($mttr <= 50) return 'success';
                        if ($mttr <= 60) return 'warning';
                        return 'danger';
                    })
                    ->sortable()
                    ->alignCenter()
                    ->description('Target: <50 min'),
                TextColumn::make('total_downtime')
                    ->label('Downtime')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $hours = floor($state / 60);
                        $mins = $state % 60;
                        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                    })
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Kaizen Metrics Group
                TextColumn::make('kaizen_submitted')
                    ->label('Kaizen Submit')
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->toggleable(),
                TextColumn::make('kaizen_closed')
                    ->label('Kaizen Closed')
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->badge()
                    ->color(function ($record) {
                        $closed = $record->kaizen_closed ?? 0;
                        if ($closed >= 1) return 'success';
                        return 'gray';
                    })
                    ->description('Target: 4/year'),
                TextColumn::make('kaizen_total_score')
                    ->label('Kaizen Score')
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->formatStateUsing(fn ($state) => $state ?: '0')
                    ->color('primary')
                    ->weight('bold')
                    ->toggleable(),
                    
                // Overall Score
                TextColumn::make('performance_score')
                    ->label('Score')
                    ->getStateUsing(function ($record) {
                        return static::calculateScore($record);
                    })
                    ->badge()
                    ->color(function ($record) {
                        $score = static::calculateScore($record);
                        if ($score >= 85) return 'success';
                        if ($score >= 70) return 'warning';
                        return 'danger';
                    })
                    ->alignCenter()
                    ->weight('bold')
                    ->size('lg')
                    ->sortable(false)
                    ->description('Max 100'),
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
            ->toolbarActions([])
            ->striped()
            ->paginated([10, 25, 50]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ListTechnicianPerformances::route('/'),
        ];
    }
}
