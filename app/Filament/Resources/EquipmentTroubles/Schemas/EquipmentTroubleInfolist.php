<?php

namespace App\Filament\Resources\EquipmentTroubles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EquipmentTroubleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trouble Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Trouble ID'),
                        
                        TextEntry::make('equipment.name')
                            ->label('Equipment'),
                        
                        TextEntry::make('title')
                            ->columnSpanFull(),
                        
                        TextEntry::make('issue_description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->markdown(),
                        
                        TextEntry::make('priority')
                            ->badge()
                            ->colors([
                                'gray' => fn ($state) => $state === 'low',
                                'warning' => fn ($state) => $state === 'medium',
                                'danger' => fn ($state) => in_array($state, ['high', 'critical'], true),
                            ]),
                        
                        TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'danger' => fn ($state) => $state === 'open',
                                'warning' => fn ($state) => $state === 'investigating',
                                'info' => fn ($state) => $state === 'in_progress',
                                'success' => fn ($state) => $state === 'resolved',
                                'gray' => fn ($state) => $state === 'closed',
                            ]),
                    ])
                    ->columns(2),
                
                Section::make('Timeline')
                    ->schema([
                        TextEntry::make('reportedBy.name')
                            ->label('Reported By'),
                        
                        TextEntry::make('reported_at')
                            ->dateTime('d M Y H:i'),
                        
                        TextEntry::make('technicians.name')
                            ->label('Assigned To')
                            ->badge()
                            ->separator(',')
                            ->default('-'),
                        
                        TextEntry::make('acknowledged_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        
                        TextEntry::make('started_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        
                        TextEntry::make('resolved_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        
                        TextEntry::make('closed_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        
                        TextEntry::make('response_time')
                            ->suffix(' minutes')
                            ->default('-'),
                        
                        TextEntry::make('resolution_time')
                            ->suffix(' minutes')
                            ->default('-'),
                    ])
                    ->columns(2),
                
                Section::make('Resolution')
                    ->schema([
                        TextEntry::make('resolution_notes')
                            ->formatStateUsing(function (?string $state): ?string {
                                if ($state === null || $state === '') {
                                    return $state;
                                }

                                $base = rtrim(asset('storage'), '/');

                                return preg_replace_callback('/<img\\b[^>]*>/i', function (array $matches) use ($base): string {
                                    $imgTag = $matches[0];

                                    if (preg_match('/data-id="([^"]+)"/i', $imgTag, $idMatch)) {
                                        $src = $base . '/' . $idMatch[1];

                                        if (preg_match('/src="[^"]*"/i', $imgTag)) {
                                            return preg_replace('/src="[^"]*"/i', 'src="' . $src . '"', $imgTag);
                                        }

                                        return preg_replace('/<img/i', '<img src="' . $src . '"', $imgTag);
                                    }

                                    $imgTag = preg_replace('/src="https?:\\/\\/[^"]*\\/storage\\/([^"]+)"/i', 'src="' . $base . '/$1"', $imgTag);
                                    $imgTag = preg_replace('/src="\\/storage\\/([^"]+)"/i', 'src="' . $base . '/$1"', $imgTag);

                                    return $imgTag;
                                }, $state);
                            })
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('-')
                            ->visible(fn ($record) => in_array($record->status, ['resolved', 'closed'])),

                        ViewEntry::make('attachments')
                            ->label('Images')
                            ->view('filament.equipment-troubles.attachments-display')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->attachments)),
                        
                        TextEntry::make('downtime_minutes')
                            ->label('Total Downtime')
                            ->suffix(' minutes')
                            ->placeholder('-')
                            ->visible(fn ($record) => !empty($record->downtime_minutes)),
                    ])
                    ->visible(fn ($record) => in_array($record->status, ['resolved', 'closed']) || !empty($record->attachments))
                    ->collapsible(),
                
            ]);
    }
}
