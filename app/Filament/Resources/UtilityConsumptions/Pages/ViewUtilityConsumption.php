<?php

namespace App\Filament\Resources\UtilityConsumptions\Pages;

use App\Filament\Resources\UtilityConsumptions\UtilityConsumptionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewUtilityConsumption extends ViewRecord
{
    protected static string $resource = UtilityConsumptionResource::class;

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
            Section::make('Record Information')
                ->columns(5)
                ->schema([
                    TextEntry::make('record_no')
                        ->label('Record No')
                        ->copyable(),
                    TextEntry::make('consumption_date')
                        ->label('Date')
                        ->date('d M Y'),
                    TextEntry::make('shift_label')
                        ->label('Shift')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('area.name')
                        ->label('Area')
                        ->placeholder('Plant-wide'),
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

            Section::make('Water Consumption')
                ->columns(4)
                ->icon(Heroicon::OutlinedBeaker)
                ->schema([
                    TextEntry::make('water_meter_start')
                        ->label('Meter Start')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' L' : '-'),
                    TextEntry::make('water_meter_end')
                        ->label('Meter End')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' L' : '-'),
                    TextEntry::make('water_consumption')
                        ->label('Consumption')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' L')
                        ->color('info')
                        ->weight('bold'),
                    TextEntry::make('water_cost')
                        ->label('Cost')
                        ->money('IDR')
                        ->placeholder('-'),
                ]),

            Section::make('Electricity Consumption')
                ->columns(4)
                ->icon(Heroicon::OutlinedBolt)
                ->schema([
                    TextEntry::make('electricity_meter_start')
                        ->label('Meter Start')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' kWh' : '-'),
                    TextEntry::make('electricity_meter_end')
                        ->label('Meter End')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' kWh' : '-'),
                    TextEntry::make('electricity_consumption')
                        ->label('Consumption')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kWh')
                        ->color('warning')
                        ->weight('bold'),
                    TextEntry::make('electricity_cost')
                        ->label('Cost')
                        ->money('IDR')
                        ->placeholder('-'),
                ]),

            Section::make('Gas Consumption')
                ->columns(4)
                ->icon(Heroicon::OutlinedFire)
                ->schema([
                    TextEntry::make('gas_meter_start')
                        ->label('Meter Start')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' kWh' : '-'),
                    TextEntry::make('gas_meter_end')
                        ->label('Meter End')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' kWh' : '-'),
                    TextEntry::make('gas_consumption')
                        ->label('Consumption')
                        ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kWh')
                        ->color('danger')
                        ->weight('bold'),
                    TextEntry::make('gas_cost')
                        ->label('Cost')
                        ->money('IDR')
                        ->placeholder('-'),
                ]),

            Section::make('Total Cost')
                ->schema([
                    TextEntry::make('total_cost')
                        ->label('Total Cost')
                        ->money('IDR')
                        ->size('lg')
                        ->weight('bold'),
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
