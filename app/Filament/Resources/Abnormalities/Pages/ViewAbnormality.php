<?php

namespace App\Filament\Resources\Abnormalities\Pages;

use App\Filament\Resources\Abnormalities\AbnormalityResource;
use App\Models\Abnormality;
use App\Models\User;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;

class ViewAbnormality extends ViewRecord
{
    protected static string $resource = AbnormalityResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $isManager = $user instanceof User && in_array($user->role, ['asisten_manager', 'manager', 'super_admin', 'supervisor']);

        return [
            Actions\EditAction::make(),

            // Assign Action
            Actions\Action::make('assign')
                ->label('Assign')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn () => $this->record->canAssign() && $isManager)
                ->form([
                    Select::make('assigned_to')
                        ->label('Assign To')
                        ->options(
                            User::whereIn('role', ['technician', 'senior_technician', 'supervisor'])
                                ->whereIn('department', ['utility', 'mechanic', 'electric'])
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($user) => [$user->gpid => "{$user->name} ({$user->department})"])
                        )
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->action(fn (array $data) => $this->record->assign($data['assigned_to']))
                ->requiresConfirmation(),

            // Start Progress Action
            Actions\Action::make('start_progress')
                ->label('Start Progress')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn () => $this->record->canStartProgress())
                ->action(fn () => $this->record->startProgress())
                ->requiresConfirmation(),

            // Mark Fixed Action
            Actions\Action::make('mark_fixed')
                ->label('Mark Fixed')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->visible(fn () => $this->record->canMarkFixed())
                ->form([
                    Textarea::make('fix_description')
                        ->label('Fix Description')
                        ->required()
                        ->rows(3),
                    FileUpload::make('fix_photo')
                        ->label('Fix Evidence Photo')
                        ->image()
                        ->disk('public')
                        ->directory('abnormalities/fixes')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                ])
                ->action(function (array $data) {
                    $this->record->markFixed($data['fix_description'], $data['fix_photo'] ?? null);
                })
                ->requiresConfirmation(),

            // Verify Action
            Actions\Action::make('verify')
                ->label('Verify')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->canVerify() && $isManager)
                ->form([
                    Textarea::make('verification_notes')
                        ->label('Verification Notes')
                        ->rows(2),
                ])
                ->action(fn (array $data) => $this->record->verify($data['verification_notes'] ?? null))
                ->requiresConfirmation(),

            // Close Action
            Actions\Action::make('close')
                ->label('Close')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn () => $this->record->canClose() && $isManager)
                ->action(fn () => $this->record->close())
                ->requiresConfirmation(),
        ];
    }
}
