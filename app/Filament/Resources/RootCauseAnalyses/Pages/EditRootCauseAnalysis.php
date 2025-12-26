<?php

namespace App\Filament\Resources\RootCauseAnalyses\Pages;

use App\Filament\Resources\RootCauseAnalyses\RootCauseAnalysisResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditRootCauseAnalysis extends EditRecord
{
    protected static string $resource = RootCauseAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function beforeSave(): void
    {
        // Prevent editing if not in editable status
        if (!$this->record->isEditable()) {
            Notification::make()
                ->title('Cannot edit this RCA')
                ->body('RCA can only be edited in draft or submitted status.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
