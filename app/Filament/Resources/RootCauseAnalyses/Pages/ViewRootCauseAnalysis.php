<?php

namespace App\Filament\Resources\RootCauseAnalyses\Pages;

use App\Filament\Resources\RootCauseAnalyses\RootCauseAnalysisResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewRootCauseAnalysis extends ViewRecord
{
    protected static string $resource = RootCauseAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->isEditable()),

            Actions\Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->canBeSubmitted())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->submit();
                    Notification::make()->title('RCA submitted for review')->success()->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('review')
                ->label('Mark as Reviewed')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn () => $this->record->canBeReviewed() && in_array(Auth::user()->role, ['asisten_manager', 'manager', 'super_admin']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->review(Auth::user()->gpid);
                    Notification::make()->title('RCA marked as reviewed')->success()->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('approve')
                ->label('Approve RCA')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeApproved() && in_array(Auth::user()->role, ['manager', 'super_admin']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve(Auth::user()->gpid);
                    Notification::make()->title('RCA approved')->success()->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('close')
                ->label('Close RCA')
                ->icon('heroicon-o-lock-closed')
                ->color('primary')
                ->visible(fn () => $this->record->canBeClosed() && in_array(Auth::user()->role, ['manager', 'super_admin']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->close();
                    Notification::make()->title('RCA closed')->success()->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
