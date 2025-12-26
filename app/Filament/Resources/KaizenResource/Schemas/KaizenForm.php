<?php

namespace App\Filament\Resources\KaizenResource\Schemas;

use App\Models\Kaizen;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Utilities\Get;

class KaizenForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isReviewer = $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);

        return $schema->components([
            Section::make('Kaizen Information')
                ->components([
                    TextInput::make('title')
                        ->label('Kaizen Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Brief description of the improvement')
                        ->disabled(fn ($record) => $record && $record->status !== Kaizen::STATUS_SUBMITTED),

                    Select::make('category')
                        ->label('Category')
                        ->required()
                        ->options([
                            'RECON' => 'RECON (Reconditioning) - Score: 5',
                            'DT_REDUCTION' => 'Downtime Reduction - Score: 3',
                            'SAFETY_QUALITY' => 'Safety & Quality - Score: 1',
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $set('score', Kaizen::calculateScore($state));
                        })
                        ->helperText('Select the category that best describes this Kaizen')
                        ->disabled(fn ($record) => $record && $record->status !== Kaizen::STATUS_SUBMITTED),

                    TextInput::make('score')
                        ->label('Score')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-calculated based on category'),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->placeholder('Detailed description of the improvement idea')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && $record->status !== Kaizen::STATUS_SUBMITTED),
                ])->columns(3),

            Section::make('Before & After')
                ->components([
                    Textarea::make('before_situation')
                        ->label('Before Situation')
                        ->rows(4)
                        ->placeholder('Describe the situation before the improvement')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && $record->status !== Kaizen::STATUS_SUBMITTED),

                    Textarea::make('after_situation')
                        ->label('After Situation / Expected Result')
                        ->rows(4)
                        ->placeholder('Describe the expected result after implementation')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && $record->status !== Kaizen::STATUS_SUBMITTED),
                ]),

            Section::make('Attachments')
                ->components([
                    FileUpload::make('attachments')
                        ->label('Attachments')
                        ->multiple()
                        ->disk('public')
                        ->directory('kaizen-attachments')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->maxSize(5120)
                        ->downloadable()
                        ->openable()
                        ->previewable()
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !in_array($record->status, [Kaizen::STATUS_SUBMITTED, Kaizen::STATUS_IN_PROGRESS])),
                ]),

            Section::make('Workflow Status')
                ->components([
                    Placeholder::make('current_status')
                        ->label('Current Status')
                        ->content(fn ($record) => $record ? Kaizen::getStatuses()[$record->status] ?? $record->status : 'New'),

                    Placeholder::make('submitted_info')
                        ->label('Submitted')
                        ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') . ' by ' . ($record?->submittedBy?->name ?? '-'))
                        ->visible(fn ($record) => $record !== null),

                    Placeholder::make('reviewed_info')
                        ->label('Reviewed By')
                        ->content(fn ($record) => $record?->reviewedBy?->name ?? '-')
                        ->visible(fn ($record) => $record && $record->reviewed_by_gpid),

                    Placeholder::make('approved_info')
                        ->label('Approved At')
                        ->content(fn ($record) => $record?->approved_at?->format('d/m/Y H:i') ?? '-')
                        ->visible(fn ($record) => $record && $record->approved_at),

                    Placeholder::make('started_info')
                        ->label('Started At')
                        ->content(fn ($record) => $record?->started_at?->format('d/m/Y H:i') ?? '-')
                        ->visible(fn ($record) => $record && $record->started_at),

                    Placeholder::make('completed_info')
                        ->label('Completed At')
                        ->content(fn ($record) => $record?->completed_at?->format('d/m/Y H:i') ?? '-')
                        ->visible(fn ($record) => $record && $record->completed_at),

                    Placeholder::make('closed_info')
                        ->label('Closed')
                        ->content(fn ($record) => $record?->closed_at?->format('d/m/Y H:i') . ' by ' . ($record?->closedBy?->name ?? '-'))
                        ->visible(fn ($record) => $record && $record->closed_at),
                ])
                ->columns(3)
                ->visible(fn ($record) => $record !== null),

            Section::make('Review Notes')
                ->components([
                    Textarea::make('review_notes')
                        ->label('Review Notes')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record && $record->review_notes),

            Section::make('Completion Details')
                ->components([
                    Textarea::make('completion_notes')
                        ->label('Completion Notes')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),

                    TextInput::make('cost_saved')
                        ->label('Cost Saved (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled(),

                    DatePicker::make('implementation_date')
                        ->label('Implementation Date')
                        ->displayFormat('d/m/Y')
                        ->disabled(),
                ])
                ->columns(2)
                ->visible(fn ($record) => $record && in_array($record->status, [Kaizen::STATUS_COMPLETED, Kaizen::STATUS_CLOSED])),

            Hidden::make('submitted_by_gpid')
                ->default(fn () => Auth::user()?->gpid),
        ]);
    }
}
