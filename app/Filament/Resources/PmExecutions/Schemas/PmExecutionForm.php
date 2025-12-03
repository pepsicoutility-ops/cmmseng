<?php

namespace App\Filament\Resources\PmExecutions\Schemas;

use App\Models\Pm_parts_usage;
use App\Models\PmSchedule;
use App\Models\Part;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PmExecutionForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('PM Execution Information')
                    ->components([
                        Select::make('pm_schedule_id')
                            ->label('PM Schedule')
                            ->options(function () {
                                $user = Auth::user();
                                $query = PmSchedule::query();
                                
                                // Filter based on role
                                if ($user->role === 'technician') {
                                    $query->where('assigned_to_gpid', $user->gpid);
                                } elseif ($user->role === 'asisten_manager') {
                                    $query->where('department', $user->department);
                                }
                                
                                return $query->where('status', 'active')
                                    ->get()
                                    ->mapWithKeys(fn ($pm) => [
                                        $pm->id => "{$pm->code} - {$pm->title}"
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $pmSchedule = PmSchedule::find($state);
                                    if ($pmSchedule && $pmSchedule->next_due_date) {
                                        $set('scheduled_date', $pmSchedule->next_due_date);
                                    } else {
                                        $set('scheduled_date', now());
                                    }
                                }
                            })
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),
                        Hidden::make('executed_by_gpid')
                            ->default(fn () => Auth::user()->gpid)
                            ->dehydrated(),
                    ])->columns(1),
                    
                Section::make('Execution Timeline')
                    ->components([
                        DateTimePicker::make('scheduled_date')
                            ->label('Scheduled Date')
                            ->required()
                            ->native(false)
                            ->default(function ($get) {
                                $pmScheduleId = $get('pm_schedule_id');
                                if ($pmScheduleId) {
                                    $pmSchedule = PmSchedule::find($pmScheduleId);
                                    return $pmSchedule?->next_due_date ?? now();
                                }
                                return now();
                            })
                            ->dehydrated(),
                        DateTimePicker::make('actual_start')
                            ->label('Actual Start')
                            ->default(now())
                            ->required()
                            ->native(false),
                        DateTimePicker::make('actual_end')
                            ->label('Actual End')
                            ->native(false)
                            ->afterOrEqual('actual_start'),
                    ])->columns(3),
                    
                Section::make('Checklist Items')
                    ->components(function ($get) {
                        $pmScheduleId = $get('pm_schedule_id');
                        
                        if (!$pmScheduleId) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('select_pm')
                                    ->content('Please select a PM Schedule first to load checklist items.')
                            ];
                        }
                        
                        $pmSchedule = PmSchedule::with('checklistItems')->find($pmScheduleId);
                        
                        if (!$pmSchedule || $pmSchedule->checklistItems->isEmpty()) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('no_checklist')
                                    ->content('No checklist items found for this PM Schedule.')
                            ];
                        }
                        
                        $fields = [];
                        
                        foreach ($pmSchedule->checklistItems->sortBy('order') as $item) {
                            $fieldName = "checklist_data.item_{$item->id}";
                            
                            switch ($item->item_type) {
                                case 'checkbox':
                                    $fields[] = Checkbox::make($fieldName)
                                        ->label($item->item_name)
                                        ->required($item->is_required);
                                    break;
                                    
                                case 'input':
                                    $fields[] = TextInput::make($fieldName)
                                        ->label($item->item_name)
                                        ->required($item->is_required);
                                    break;
                                    
                                case 'photo':
                                    $fields[] = FileUpload::make($fieldName)
                                        ->label($item->item_name)
                                        ->image()
                                        ->directory('pm-executions/checklist-photos')
                                        ->required($item->is_required);
                                    break;
                                    
                                case 'dropdown':
                                    // Assuming dropdown_options stored in item
                                    $fields[] = Select::make($fieldName)
                                        ->label($item->item_name)
                                        ->options([
                                            'good' => 'Good',
                                            'fair' => 'Fair',
                                            'poor' => 'Poor',
                                            'needs_replacement' => 'Needs Replacement',
                                        ])
                                        ->required($item->is_required)
                                        ->native(false);
                                    break;
                            }
                        }
                        
                        return $fields;
                    })->columns(2),
                    
                Section::make('Parts Used')
                    ->description('Record parts used during this PM execution')
                    ->components([
                        Repeater::make('partsUsage')
                            ->relationship('partsUsage')
                            ->schema([
                                Select::make('part_id')
                                    ->label('Part')
                                    ->options(Part::all()->mapWithKeys(fn ($part) => [
                                        $part->id => "{$part->part_number} - {$part->name} (Stock: {$part->current_stock})"
                                    ]))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $part = Part::find($state);
                                            if ($part) {
                                                $quantity = 1;
                                                $set('cost', $part->unit_price * $quantity);
                                            }
                                        }
                                    }),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $partId = $get('part_id');
                                        if ($partId && $state) {
                                            $part = Part::find($partId);
                                            if ($part) {
                                                $set('cost', $part->unit_price * $state);
                                            }
                                        }
                                    }),
                                TextInput::make('cost')
                                    ->label('Total Cost')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Part')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->collapsed(),
                    
                Section::make('Additional Information')
                    ->components([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('photos')
                            ->label('Photos')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->directory('pm-executions/photos')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
