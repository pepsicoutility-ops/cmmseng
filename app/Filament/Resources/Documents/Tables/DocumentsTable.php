<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Area;
use App\Models\Document;
use App\Models\DocumentAcknowledgment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_no')
                    ->label('Document No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'opl' => 'info',
                        'sop' => 'primary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '-')),

                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_review' => 'warning',
                        'approved' => 'info',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => Document::getStatuses()[$state] ?? $state),

                TextColumn::make('version')
                    ->label('Ver.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('acknowledgments_count')
                    ->label('Reads')
                    ->counts('acknowledgments')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(Document::getTypes()),

                SelectFilter::make('category')
                    ->options(Document::getCategories()),

                SelectFilter::make('status')
                    ->options(Document::getStatuses()),

                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(Area::pluck('name', 'id')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, [Document::STATUS_DRAFT])),

                // Submit for Review
                Action::make('submit_review')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->canSubmitForReview())
                    ->action(fn ($record) => $record->submitForReview())
                    ->requiresConfirmation(),

                // Approve Action
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canApprove() && DocumentResource::userCanApprove())
                    ->form([
                        Textarea::make('review_notes')
                            ->label('Review Notes (optional)')
                            ->rows(2),
                    ])
                    ->action(fn ($record, array $data) => $record->approve($data['review_notes'] ?? null))
                    ->requiresConfirmation(),

                // Reject Action
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canApprove() && DocumentResource::userCanApprove())
                    ->form([
                        Textarea::make('review_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(fn ($record, array $data) => $record->reject($data['review_notes']))
                    ->requiresConfirmation(),

                // Publish Action
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('primary')
                    ->visible(fn ($record) => $record->canPublish() && DocumentResource::userCanApprove())
                    ->action(fn ($record) => $record->publish())
                    ->requiresConfirmation(),

                // Acknowledge Action
                Action::make('acknowledge')
                    ->label('I have read this')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === Document::STATUS_PUBLISHED && !$record->isAcknowledgedBy(Auth::user()->gpid))
                    ->action(function ($record) {
                        DocumentAcknowledgment::create([
                            'document_id' => $record->id,
                            'gpid' => Auth::user()->gpid,
                            'acknowledged_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Acknowledge Document')
                    ->modalDescription('By clicking confirm, you acknowledge that you have read and understood this document.'),

                // Archive Action
                Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn ($record) => $record->canArchive() && DocumentResource::userCanApprove())
                    ->action(fn ($record) => $record->archive())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => DocumentResource::userCanApprove()),
                ]),
            ]);
    }
}
