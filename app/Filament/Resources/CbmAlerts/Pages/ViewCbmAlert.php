<?php

namespace App\Filament\Resources\CbmAlerts\Pages;

use App\Filament\Resources\CbmAlerts\CbmAlertResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCbmAlert extends ViewRecord
{
    protected static string $resource = CbmAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('acknowledge')
                ->label('Acknowledge')
                ->icon('heroicon-o-check')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'open')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->acknowledge();
                    Notification::make()->title('Alert acknowledged')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('start_progress')
                ->label('Start Progress')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn () => $this->record->status === 'acknowledged')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->startProgress();
                    Notification::make()->title('Working on alert')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('resolve')
                ->label('Resolve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['acknowledged', 'in_progress']))
                ->form([
                    \Filament\Forms\Components\Textarea::make('resolution_notes')
                        ->label('Resolution Notes')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->resolve($data['resolution_notes']);
                    Notification::make()->title('Alert resolved')->success()->send();
                    $this->refreshFormData(['status', 'resolution_notes', 'resolved_at']);
                }),

            Actions\Action::make('close')
                ->label('Close')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'resolved')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->close();
                    Notification::make()->title('Alert closed')->success()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Alert Details')
                ->columns(4)
                ->schema([
                    TextEntry::make('alert_no')
                        ->label('Alert No')
                        ->copyable(),
                    TextEntry::make('checklist_type_label')
                        ->label('Checklist Type')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('parameter_name')
                        ->label('Parameter')
                        ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                    TextEntry::make('recorded_value')
                        ->label('Recorded Value')
                        ->formatStateUsing(fn ($state) => number_format($state, 2)),
                    TextEntry::make('threshold_value')
                        ->label('Threshold Value')
                        ->formatStateUsing(fn ($state) => number_format($state, 2)),
                    TextEntry::make('alert_type_label')
                        ->label('Alert Type')
                        ->badge(),
                    TextEntry::make('severity')
                        ->label('Severity')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            'info' => 'info',
                            default => 'gray',
                        }),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'open' => 'danger',
                            'acknowledged' => 'warning',
                            'in_progress' => 'info',
                            'resolved' => 'success',
                            'closed' => 'gray',
                            default => 'gray',
                        }),
                ]),

            Section::make('Response Timeline')
                ->columns(5)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Detected At')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('acknowledgedBy.name')
                        ->label('Acknowledged By')
                        ->placeholder('-'),
                    TextEntry::make('acknowledged_at')
                        ->label('Acknowledged At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('resolvedBy.name')
                        ->label('Resolved By')
                        ->placeholder('-'),
                    TextEntry::make('resolved_at')
                        ->label('Resolved At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                ]),

            Section::make('Resolution')
                ->schema([
                    TextEntry::make('resolution_notes')
                        ->label('Resolution Notes')
                        ->placeholder('No resolution notes yet')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->resolution_notes),
        ]);
    }
}
