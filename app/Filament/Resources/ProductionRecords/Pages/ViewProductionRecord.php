<?php

namespace App\Filament\Resources\ProductionRecords\Pages;

use App\Filament\Resources\ProductionRecords\ProductionRecordResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProductionRecord extends ViewRecord
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->submit();
                    Notification::make()->title('Record submitted for verification')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('verify')
                ->label('Verify')
                ->icon('heroicon-o-check')
                ->color('info')
                ->visible(fn () => $this->record->status === 'submitted')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->verify();
                    Notification::make()->title('Record verified')->success()->send();
                    $this->refreshFormData(['status', 'verified_by_gpid', 'verified_at']);
                }),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'verified')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve();
                    Notification::make()->title('Record approved')->success()->send();
                    $this->refreshFormData(['status', 'approved_by_gpid', 'approved_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['submitted', 'verified']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject();
                    Notification::make()->title('Record rejected - returned to draft')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Production Information')
                ->columns(3)
                ->schema([
                    TextEntry::make('record_no')
                        ->label('Record No')
                        ->copyable(),
                    TextEntry::make('production_date')
                        ->label('Production Date')
                        ->date('d M Y'),
                    TextEntry::make('shift_label')
                        ->label('Shift')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('area.name')
                        ->label('Area'),
                    TextEntry::make('subArea.name')
                        ->label('Line')
                        ->placeholder('-'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'draft' => 'gray',
                            'submitted' => 'warning',
                            'verified' => 'info',
                            'approved' => 'success',
                            default => 'gray',
                        }),
                ]),

            Section::make('Production Metrics')
                ->columns(3)
                ->schema([
                    TextEntry::make('weight_kg')
                        ->label('Total Production')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kg'),
                    TextEntry::make('good_product_kg')
                        ->label('Good Product')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kg')
                        ->color('success'),
                    TextEntry::make('waste_kg')
                        ->label('Waste/Reject')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kg')
                        ->color('danger'),
                    TextEntry::make('yield_percentage')
                        ->label('Yield')
                        ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                        ->color(fn ($state) => $state >= 95 ? 'success' : ($state >= 90 ? 'warning' : 'danger')),
                    TextEntry::make('production_hours')
                        ->label('Production Time')
                        ->formatStateUsing(fn ($state) => $state . ' minutes'),
                    TextEntry::make('downtime_minutes')
                        ->label('Downtime')
                        ->formatStateUsing(fn ($state) => $state . ' minutes'),
                ]),

            Section::make('Workflow')
                ->columns(3)
                ->schema([
                    TextEntry::make('recordedBy.name')
                        ->label('Recorded By'),
                    TextEntry::make('created_at')
                        ->label('Recorded At')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('verifiedBy.name')
                        ->label('Verified By')
                        ->placeholder('-'),
                    TextEntry::make('verified_at')
                        ->label('Verified At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('approvedBy.name')
                        ->label('Approved By')
                        ->placeholder('-'),
                    TextEntry::make('approved_at')
                        ->label('Approved At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                ]),

            Section::make('Notes')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->placeholder('No notes'),
                ]),
        ]);
    }
}
