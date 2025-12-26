<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentAcknowledgment;
use App\Models\User;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $isManager = $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin']);

        return [
            Actions\EditAction::make()
                ->visible(fn () => in_array($this->record->status, [Document::STATUS_DRAFT])),

            // Submit for Review
            Actions\Action::make('submit_review')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->canSubmitForReview())
                ->action(fn () => $this->record->submitForReview())
                ->requiresConfirmation(),

            // Approve Action
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canApprove() && $isManager)
                ->form([
                    Textarea::make('review_notes')
                        ->label('Review Notes (optional)')
                        ->rows(2),
                ])
                ->action(fn (array $data) => $this->record->approve($data['review_notes'] ?? null))
                ->requiresConfirmation(),

            // Reject Action
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canApprove() && $isManager)
                ->form([
                    Textarea::make('review_notes')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(2),
                ])
                ->action(fn (array $data) => $this->record->reject($data['review_notes']))
                ->requiresConfirmation(),

            // Publish Action
            Actions\Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-globe-alt')
                ->color('primary')
                ->visible(fn () => $this->record->canPublish() && $isManager)
                ->action(fn () => $this->record->publish())
                ->requiresConfirmation(),

            // Acknowledge Action
            Actions\Action::make('acknowledge')
                ->label('I have read this')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => $this->record->status === Document::STATUS_PUBLISHED && !$this->record->isAcknowledgedBy($user->gpid))
                ->action(function () use ($user) {
                    DocumentAcknowledgment::create([
                        'document_id' => $this->record->id,
                        'gpid' => $user->gpid,
                        'acknowledged_at' => now(),
                    ]);
                })
                ->requiresConfirmation()
                ->modalHeading('Acknowledge Document')
                ->modalDescription('By clicking confirm, you acknowledge that you have read and understood this document.'),

            // Archive Action
            Actions\Action::make('archive')
                ->label('Archive')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => $this->record->canArchive() && $isManager)
                ->action(fn () => $this->record->archive())
                ->requiresConfirmation(),
        ];
    }
}
