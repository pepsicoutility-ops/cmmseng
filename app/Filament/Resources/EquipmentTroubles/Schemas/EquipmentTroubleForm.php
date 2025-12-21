<?php

namespace App\Filament\Resources\EquipmentTroubles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EquipmentTroubleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trouble Information')
                    ->schema([
                        Select::make('equipment_id')
                            ->relationship('equipment', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Brief description of the issue'),
                        
                        Textarea::make('issue_description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Detailed description of the issue, symptoms, and observations'),
                        
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->required()
                            ->default('medium')
                            ->native(false),
                        
                        Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'investigating' => 'Investigating',
                                'in_progress' => 'In Progress',
                                'resolved' => 'Resolved',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('open')
                            ->native(false),
                    ])
                    ->columns(2),
                
                Section::make('Assignment')
                    ->schema([
                        Hidden::make('reported_by')
                            ->default(fn () => Auth::id()),
                        
                        DateTimePicker::make('reported_at')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->disabled(fn () => Auth::user()->role === 'technician')
                            ->dehydrated(),
                        
                        Select::make('technicians')
                            ->label('Assigned Technicians')
                            ->relationship('technicians', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->maxItems(2)
                            ->placeholder('Select up to 2 technicians')
                            ->disabled(fn () => Auth::user()->role === 'technician')
                            ->dehydrated(fn () => Auth::user()->role !== 'technician')
                            ->helperText('Assistant manager can assign up to 2 technicians')
                            ->columnSpanFull(),
                        
                        DateTimePicker::make('acknowledged_at')
                            ->native(false)
                            ->visible(fn ($get) => $get('status') !== 'open'),
                        
                        DateTimePicker::make('started_at')
                            ->native(false)
                            ->visible(fn ($get) => in_array($get('status'), ['in_progress', 'resolved', 'closed'])),
                        
                        DateTimePicker::make('resolved_at')
                            ->native(false)
                            ->visible(fn ($get) => in_array($get('status'), ['resolved', 'closed'])),
                        
                        DateTimePicker::make('closed_at')
                            ->native(false)
                            ->visible(fn ($get) => $get('status') === 'closed')
                            // Hanya manager/asisten_manager yang bisa close
                            ->disabled(fn () => !in_array(Auth::user()->role, ['super_admin', 'manager', 'asisten_manager'])),
                    ])
                    ->columns(2),
                
                Section::make('Resolution')
                    ->schema([
                        RichEditor::make('resolution_notes')
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array($get('status'), ['resolved', 'closed']))
                            ->placeholder('Describe the solution, parts replaced, actions taken'),
                        
                        TextInput::make('downtime_minutes')
                            ->numeric()
                            ->suffix('minutes')
                            ->placeholder('Total equipment downtime'),
                        
                        FileUpload::make('attachments')
                            ->multiple()
                            ->disk('public')
                            ->directory('trouble-attachments')
                            ->image()
                            ->maxSize(5120)
                            ->columnSpanFull()
                            ->helperText('Upload photos, diagrams, or documentation'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
