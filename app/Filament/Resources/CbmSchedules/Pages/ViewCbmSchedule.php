<?php

namespace App\Filament\Resources\CbmSchedules\Pages;

use App\Filament\Resources\CbmSchedules\CbmScheduleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCbmSchedule extends ViewRecord
{
    protected static string $resource = CbmScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule Information')
                ->columns(3)
                ->schema([
                    TextEntry::make('schedule_no')
                        ->label('Schedule No')
                        ->copyable(),
                    TextEntry::make('checklist_type_label')
                        ->label('Checklist Type')
                        ->badge(),
                    TextEntry::make('area.name')
                        ->label('Area'),
                    TextEntry::make('asset.name')
                        ->label('Asset')
                        ->placeholder('-'),
                    TextEntry::make('frequency_label')
                        ->label('Frequency')
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('shifts_per_day')
                        ->label('Shifts Per Day'),
                    TextEntry::make('start_date')
                        ->label('Start Date')
                        ->date('d M Y'),
                    TextEntry::make('end_date')
                        ->label('End Date')
                        ->date('d M Y')
                        ->placeholder('No end date'),
                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean(),
                ]),

            Section::make('Notes')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->placeholder('No notes'),
                ]),

            Section::make('Timestamps')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('d M Y H:i'),
                ]),
        ]);
    }
}
