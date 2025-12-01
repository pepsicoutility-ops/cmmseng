<?php

namespace App\Filament\Widgets;

use App\Models\AhuChecklist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AhuTableWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    
    protected int|string|array $columnSpan = 'full';
    
    public static function canViewAny(): bool
    {
        // Only visible on UtilityPerformanceAnalysis page, not main dashboard
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('AHU - Recent Checklists (Last 7 Days)')
            ->query(
                AhuChecklist::query()
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
                
                Tables\Columns\TextColumn::make('total_pf')
                    ->label('Total PF')
                    ->state(function (AhuChecklist $record): int {
                        $pfFields = [
                            'ahu_mb_1_1_pf', 'ahu_mb_1_2_pf', 'ahu_mb_1_3_pf',
                            'pau_mb_1_pf', 'pau_mb_pr_1a_pf', 'pau_mb_pr_1b_pf', 'pau_mb_pr_1c_pf',
                            'ahu_vrf_mb_ms_1a_pf', 'ahu_vrf_mb_ms_1b_pf', 'ahu_vrf_mb_ms_1c_pf',
                            'ahu_vrf_mb_ss_1a_pf', 'ahu_vrf_mb_ss_1b_pf', 'ahu_vrf_mb_ss_1c_pf',
                            'if_pre_filter_a', 'if_pre_filter_b', 'if_pre_filter_c',
                            'if_pre_filter_d', 'if_pre_filter_e', 'if_pre_filter_f'
                        ];
                        return collect($pfFields)->sum(fn($field) => $record->$field ?? 0);
                    })
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('total_mf')
                    ->label('Total MF')
                    ->state(function (AhuChecklist $record): int {
                        $mfFields = [
                            'ahu_mb_1_1_mf', 'ahu_mb_1_2_mf', 'ahu_mb_1_3_mf',
                            'pau_mb_pr_1a_mf', 'pau_mb_pr_1b_mf', 'pau_mb_pr_1c_mf',
                            'if_medium_a', 'if_medium_b', 'if_medium_c',
                            'if_medium_d', 'if_medium_e', 'if_medium_f'
                        ];
                        return collect($mfFields)->sum(fn($field) => $record->$field ?? 0);
                    })
                    ->badge()
                    ->color('warning'),
                
                Tables\Columns\TextColumn::make('total_hf')
                    ->label('Total HF')
                    ->state(function (AhuChecklist $record): int {
                        $hfFields = [
                            'ahu_mb_1_1_hf', 'ahu_mb_1_2_hf', 'ahu_mb_1_3_hf',
                            'pau_mb_pr_1a_hf', 'pau_mb_pr_1b_hf', 'pau_mb_pr_1c_hf',
                            'if_hepa_a', 'if_hepa_b', 'if_hepa_c',
                            'if_hepa_d', 'if_hepa_e', 'if_hepa_f'
                        ];
                        return collect($hfFields)->sum(fn($field) => $record->$field ?? 0);
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('gpid')
                    ->label('Created By')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->striped();
    }
}
