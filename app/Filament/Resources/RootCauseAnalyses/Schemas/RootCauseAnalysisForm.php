<?php

namespace App\Filament\Resources\RootCauseAnalyses\Schemas;

use App\Models\RootCauseAnalysis;
use App\Models\WorkOrder;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class RootCauseAnalysisForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Work Order Information')
                ->icon(Heroicon::OutlinedWrench)
                ->components([
                    Select::make('work_order_id')
                        ->label('Work Order')
                        ->options(function () {
                            return WorkOrder::where('rca_required', true)
                                ->whereDoesntHave('rootCauseAnalysis')
                                ->orWhereHas('rootCauseAnalysis', function ($q) {
                                    $q->where('status', 'draft');
                                })
                                ->pluck('wo_number', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($record) => $record !== null)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $wo = WorkOrder::find($state);
                                if ($wo) {
                                    $set('problem_statement', $wo->description);
                                }
                            }
                        }),

                    Placeholder::make('wo_details')
                        ->label('WO Details')
                        ->content(function (callable $get) {
                            $woId = $get('work_order_id');
                            if (!$woId) return '-';
                            $wo = WorkOrder::find($woId);
                            if (!$wo) return '-';
                            return "Equipment: " . ($wo->subAsset->name ?? $wo->asset->name ?? 'N/A') .
                                " | Downtime: {$wo->total_downtime} min | Priority: {$wo->priority}";
                        })
                        ->visible(fn (callable $get) => $get('work_order_id')),
                ])
                ->columns(1),

            Section::make('Problem Analysis')
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->components([
                    Textarea::make('problem_statement')
                        ->label('Problem Statement')
                        ->helperText('Clear and specific description of the problem')
                        ->required()
                        ->rows(3)
                        ->maxLength(65535),

                    Textarea::make('immediate_cause')
                        ->label('Immediate Cause')
                        ->helperText('What directly caused the issue?')
                        ->rows(2)
                        ->maxLength(65535),

                    Select::make('analysis_method')
                        ->label('Analysis Method')
                        ->options([
                            '5_whys' => '5 Whys Analysis',
                            'fishbone' => 'Fishbone Diagram',
                            'fault_tree' => 'Fault Tree Analysis',
                            'other' => 'Other Method',
                        ])
                        ->default('5_whys')
                        ->required()
                        ->live(),
                ])
                ->columns(1),

            // 5 Whys Section
            Section::make('5 Whys Analysis')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->components([
                    Repeater::make('five_whys')
                        ->label('Why Analysis')
                        ->schema([
                            TextInput::make('why')
                                ->label('Why?')
                                ->required(),
                            Textarea::make('answer')
                                ->label('Answer')
                                ->required()
                                ->rows(2),
                        ])
                        ->columns(1)
                        ->minItems(1)
                        ->maxItems(7)
                        ->defaultItems(5)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['why'] ?? null),
                ])
                ->visible(fn (callable $get) => $get('analysis_method') === '5_whys')
                ->collapsible(),

            // Fishbone Section
            Section::make('Fishbone Diagram (6M)')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->components([
                    Textarea::make('fishbone_data.man')
                        ->label('Man (People)')
                        ->helperText('Human factors: skills, training, fatigue, etc.')
                        ->rows(2),
                    Textarea::make('fishbone_data.machine')
                        ->label('Machine (Equipment)')
                        ->helperText('Equipment factors: wear, maintenance, calibration, etc.')
                        ->rows(2),
                    Textarea::make('fishbone_data.method')
                        ->label('Method (Process)')
                        ->helperText('Process factors: procedures, work instructions, etc.')
                        ->rows(2),
                    Textarea::make('fishbone_data.material')
                        ->label('Material')
                        ->helperText('Material factors: quality, specifications, storage, etc.')
                        ->rows(2),
                    Textarea::make('fishbone_data.measurement')
                        ->label('Measurement')
                        ->helperText('Measurement factors: calibration, accuracy, etc.')
                        ->rows(2),
                    Textarea::make('fishbone_data.environment')
                        ->label('Environment')
                        ->helperText('Environmental factors: temperature, humidity, cleanliness, etc.')
                        ->rows(2),
                ])
                ->visible(fn (callable $get) => $get('analysis_method') === 'fishbone')
                ->columns(2)
                ->collapsible(),

            Section::make('Root Cause & Actions')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->components([
                    Textarea::make('root_cause')
                        ->label('Root Cause')
                        ->helperText('The fundamental underlying cause')
                        ->required()
                        ->rows(3)
                        ->maxLength(65535),

                    Select::make('root_cause_category')
                        ->label('Root Cause Category')
                        ->options([
                            'man' => 'Man (People)',
                            'machine' => 'Machine (Equipment)',
                            'method' => 'Method (Process)',
                            'material' => 'Material',
                            'measurement' => 'Measurement',
                            'environment' => 'Environment',
                        ]),

                    Textarea::make('corrective_actions')
                        ->label('Corrective Actions')
                        ->helperText('Immediate actions to fix the problem')
                        ->required()
                        ->rows(3)
                        ->maxLength(65535),

                    Textarea::make('preventive_actions')
                        ->label('Preventive Actions')
                        ->helperText('Long-term actions to prevent recurrence')
                        ->rows(3)
                        ->maxLength(65535),

                    DatePicker::make('action_deadline')
                        ->label('Action Deadline'),

                    Select::make('action_responsible_gpid')
                        ->label('Responsible Person')
                        ->options(fn () => User::whereIn('role', ['technician', 'asisten_manager', 'manager'])
                            ->where('is_active', true)
                            ->pluck('name', 'gpid'))
                        ->searchable(),
                ])
                ->columns(2),

            Section::make('AI Suggestions')
                ->icon(Heroicon::OutlinedSparkles)
                ->components([
                    Toggle::make('ai_assisted')
                        ->label('Use AI Assistance')
                        ->helperText('Enable to get AI-generated suggestions based on historical data')
                        ->default(false)
                        ->live(),

                    Placeholder::make('ai_suggestions_display')
                        ->label('AI Suggestions')
                        ->content(function ($record) {
                            if (!$record || !$record->ai_suggestions) {
                                return 'Save the RCA and use the "Generate AI Suggestions" action to get recommendations.';
                            }
                            $suggestions = $record->ai_suggestions;
                            return nl2br(e(is_array($suggestions) ? json_encode($suggestions, JSON_PRETTY_PRINT) : $suggestions));
                        })
                        ->visible(fn (callable $get) => $get('ai_assisted')),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Effectiveness Tracking')
                ->icon(Heroicon::OutlinedChartBar)
                ->components([
                    Toggle::make('recurrence_check')
                        ->label('Issue Recurred?')
                        ->helperText('Check if the issue has recurred after RCA implementation'),

                    DatePicker::make('recurrence_check_date')
                        ->label('Recurrence Check Date'),

                    Textarea::make('effectiveness_notes')
                        ->label('Effectiveness Notes')
                        ->rows(2),
                ])
                ->columns(3)
                ->visible(fn ($record) => $record && in_array($record->status, ['approved', 'closed']))
                ->collapsible(),

            Hidden::make('created_by_gpid')
                ->default(fn () => Auth::user()->gpid),

            Hidden::make('status')
                ->default('draft'),
        ]);
    }
}
