<?php

namespace App\Filament\Widgets;

use App\Models\EquipmentPrediction;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;


class AiInsightsTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EquipmentPrediction::query()
                    ->whereDate('predicted_at', now()->toDateString())
                    ->where(function($q) {
                        $q->where('is_anomaly', true)
                          ->orWhere('equipment_priority', '>=', 7);
                    })
                    ->latest('predicted_at')
            )
            ->columns([
                TextColumn::make('predicted_at')
                    ->label('Time')
                    ->dateTime('H:i')
                    ->sortable(),

                BadgeColumn::make('equipment_type')
                    ->label('Equipment')
                    ->formatStateUsing(fn($state) => strtoupper(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => 'chiller1',
                        'info' => 'chiller2',
                        'warning' => 'compressor1',
                        'success' => 'compressor2',
                        'secondary' => 'ahu',
                    ]),

                IconColumn::make('is_anomaly')
                    ->label('Anomaly')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                BadgeColumn::make('risk_signal')
                    ->label('Risk Level')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'info' => 'medium',
                        'success' => 'low',
                    ]),

                TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . '%' : 'N/A')
                    ->sortable(),

                BadgeColumn::make('severity_level')
                    ->label('Severity')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'warning',
                        'success' => 'normal',
                    ]),

                TextColumn::make('equipment_priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? "{$state}/10" : 'N/A')
                    ->colors([
                        'danger' => fn($state) => $state >= 8,
                        'warning' => fn($state) => $state >= 5 && $state < 8,
                        'success' => fn($state) => $state < 5,
                    ])
                    ->sortable(),

                TextColumn::make('root_cause')
                    ->label('Root Cause')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('technical_recommendations')
                    ->label('Recommendations')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn($record) => 'AI Insight Details - ' . strtoupper($record->equipment_type))
                    ->modalContent(fn($record) => view('filament.widgets.ai-insight-details', [
                        'prediction' => $record
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('predicted_at', 'desc')
            ->paginated([10, 25, 50])
            ->heading('AI Equipment Insights & Recommendations');
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'super_admin' ||
            $user->department === 'utility'
        );
    }
}
