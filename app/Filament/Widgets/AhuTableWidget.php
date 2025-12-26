<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
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
        // Get all records from last 30 days and filter by individual field thresholds
        $records = AhuChecklist::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at')
            ->get()
            ->filter(function ($record) {
                // Check if ANY individual field exceeds warning thresholds
                $pfFields = [
                    'ahu_mb_1_1_pf', 'ahu_mb_1_2_pf', 'ahu_mb_1_3_pf',
                    'pau_mb_1_pf', 'pau_mb_pr_1a_pf', 'pau_mb_pr_1b_pf', 'pau_mb_pr_1c_pf',
                    'ahu_vrf_mb_ms_1a_pf', 'ahu_vrf_mb_ms_1b_pf', 'ahu_vrf_mb_ms_1c_pf',
                    'ahu_vrf_mb_ss_1a_pf', 'ahu_vrf_mb_ss_1b_pf', 'ahu_vrf_mb_ss_1c_pf',
                    'if_pre_filter_a', 'if_pre_filter_b', 'if_pre_filter_c',
                    'if_pre_filter_d', 'if_pre_filter_e', 'if_pre_filter_f'
                ];
                
                $mfFields = [
                    'ahu_mb_1_1_mf', 'ahu_mb_1_2_mf', 'ahu_mb_1_3_mf',
                    'pau_mb_pr_1a_mf', 'pau_mb_pr_1b_mf', 'pau_mb_pr_1c_mf',
                    'if_medium_a', 'if_medium_b', 'if_medium_c',
                    'if_medium_d', 'if_medium_e', 'if_medium_f'
                ];
                
                $hfFields = [
                    'ahu_mb_1_1_hf', 'ahu_mb_1_2_hf', 'ahu_mb_1_3_hf',
                    'pau_mb_pr_1a_hf', 'pau_mb_pr_1b_hf', 'pau_mb_pr_1c_hf',
                    'if_hepa_a', 'if_hepa_b', 'if_hepa_c',
                    'if_hepa_d', 'if_hepa_e', 'if_hepa_f'
                ];
                
                // Check individual PF fields >= 100 (warning threshold)
                foreach ($pfFields as $field) {
                    if ((int)($record->$field ?? 0) >= 100) {
                        return true;
                    }
                }
                
                // Check individual MF fields >= 200 (warning threshold)
                foreach ($mfFields as $field) {
                    if ((int)($record->$field ?? 0) >= 200) {
                        return true;
                    }
                }
                
                // Check individual HF fields >= 400 (warning threshold)
                foreach ($hfFields as $field) {
                    if ((int)($record->$field ?? 0) >= 400) {
                        return true;
                    }
                }
                
                return false;
            });
        
        return $table
            ->heading('🚨 AHU Filters - Replacement Required (Last 30 Days)')
            ->description('Individual filters exceed thresholds: PF >100 (⚠️150), MF >200 (⚠️250), HF >400 (⚠️450)')
            ->query(
                AhuChecklist::query()
                    ->whereIn('id', $records->pluck('id')->toArray())
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                
                TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'info',
                        2 => 'warning',
                        3 => 'success',
                        default => 'gray'
                    }),
                
                TextColumn::make('critical_pf_fields')
                    ->label('Critical PF')
                    ->state(function (AhuChecklist $record): string {
                        $pfFields = [
                            'ahu_mb_1_1_pf' => 'MB1.1', 'ahu_mb_1_2_pf' => 'MB1.2', 'ahu_mb_1_3_pf' => 'MB1.3',
                            'pau_mb_1_pf' => 'PAU-MB1', 'pau_mb_pr_1a_pf' => 'PAU-1A', 'pau_mb_pr_1b_pf' => 'PAU-1B', 'pau_mb_pr_1c_pf' => 'PAU-1C',
                            'ahu_vrf_mb_ms_1a_pf' => 'VRF-MS-1A', 'ahu_vrf_mb_ms_1b_pf' => 'VRF-MS-1B', 'ahu_vrf_mb_ms_1c_pf' => 'VRF-MS-1C',
                            'ahu_vrf_mb_ss_1a_pf' => 'VRF-SS-1A', 'ahu_vrf_mb_ss_1b_pf' => 'VRF-SS-1B', 'ahu_vrf_mb_ss_1c_pf' => 'VRF-SS-1C',
                            'if_pre_filter_a' => 'IF-A', 'if_pre_filter_b' => 'IF-B', 'if_pre_filter_c' => 'IF-C',
                            'if_pre_filter_d' => 'IF-D', 'if_pre_filter_e' => 'IF-E', 'if_pre_filter_f' => 'IF-F'
                        ];
                        $warning = [];
                        $danger = [];
                        foreach ($pfFields as $field => $label) {
                            $value = (int)($record->$field ?? 0);
                            if ($value > 150) {
                                $danger[] = "$label ($value)";
                            } elseif ($value >= 100) {
                                $warning[] = "$label ($value)";
                            }
                        }
                        $result = [];
                        if (!empty($danger)) {
                            $result[] = '<span style="color: #ef4444; font-weight: 600;">[DANGER] ' . implode(', ', $danger) . '</span>';
                        }
                        if (!empty($warning)) {
                            $result[] = '<span style="color: #f59e0b; font-weight: 600;">[WARNING] ' . implode(', ', $warning) . '</span>';
                        }
                        return !empty($result) ? implode(' ', $result) : '-';
                    })
                    ->html()
                    ->wrap(),
                
                TextColumn::make('critical_mf_fields')
                    ->label('Critical MF')
                    ->state(function (AhuChecklist $record): string {
                        $mfFields = [
                            'ahu_mb_1_1_mf' => 'MB1.1', 'ahu_mb_1_2_mf' => 'MB1.2', 'ahu_mb_1_3_mf' => 'MB1.3',
                            'pau_mb_pr_1a_mf' => 'PAU-1A', 'pau_mb_pr_1b_mf' => 'PAU-1B', 'pau_mb_pr_1c_mf' => 'PAU-1C',
                            'if_medium_a' => 'IF-A', 'if_medium_b' => 'IF-B', 'if_medium_c' => 'IF-C',
                            'if_medium_d' => 'IF-D', 'if_medium_e' => 'IF-E', 'if_medium_f' => 'IF-F'
                        ];
                        $warning = [];
                        $danger = [];
                        foreach ($mfFields as $field => $label) {
                            $value = (int)($record->$field ?? 0);
                            if ($value > 250) {
                                $danger[] = "$label ($value)";
                            } elseif ($value >= 200) {
                                $warning[] = "$label ($value)";
                            }
                        }
                        $result = [];
                        if (!empty($danger)) {
                            $result[] = '<span style="color: #ef4444; font-weight: 600;">[DANGER] ' . implode(', ', $danger) . '</span>';
                        }
                        if (!empty($warning)) {
                            $result[] = '<span style="color: #f59e0b; font-weight: 600;">[WARNING] ' . implode(', ', $warning) . '</span>';
                        }
                        return !empty($result) ? implode(' ', $result) : '-';
                    })
                    ->html()
                    ->wrap(),
                
                TextColumn::make('critical_hf_fields')
                    ->label('Critical HF')
                    ->state(function (AhuChecklist $record): string {
                        $hfFields = [
                            'ahu_mb_1_1_hf' => 'MB1.1', 'ahu_mb_1_2_hf' => 'MB1.2', 'ahu_mb_1_3_hf' => 'MB1.3',
                            'pau_mb_pr_1a_hf' => 'PAU-1A', 'pau_mb_pr_1b_hf' => 'PAU-1B', 'pau_mb_pr_1c_hf' => 'PAU-1C',
                            'if_hepa_a' => 'IF-A', 'if_hepa_b' => 'IF-B', 'if_hepa_c' => 'IF-C',
                            'if_hepa_d' => 'IF-D', 'if_hepa_e' => 'IF-E', 'if_hepa_f' => 'IF-F'
                        ];
                        $warning = [];
                        $danger = [];
                        foreach ($hfFields as $field => $label) {
                            $value = (int)($record->$field ?? 0);
                            if ($value > 450) {
                                $danger[] = "$label ($value)";
                            } elseif ($value >= 400) {
                                $warning[] = "$label ($value)";
                            }
                        }
                        $result = [];
                        if (!empty($danger)) {
                            $result[] = '<span style="color: #ef4444; font-weight: 600;">[DANGER] ' . implode(', ', $danger) . '</span>';
                        }
                        if (!empty($warning)) {
                            $result[] = '<span style="color: #f59e0b; font-weight: 600;">[WARNING] ' . implode(', ', $warning) . '</span>';
                        }
                        return !empty($result) ? implode(' ', $result) : '-';
                    })
                    ->html()
                    ->wrap(),
                
                TextColumn::make('gpid')
                    ->label('Created By')
                    ->searchable(),
                
                TextColumn::make('notes')
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
