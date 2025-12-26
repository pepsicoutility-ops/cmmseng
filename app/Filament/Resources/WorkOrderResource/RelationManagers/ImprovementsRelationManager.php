<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section; // Pastikan namespace ini sesuai v4
use Filament\Schemas\Schema; // v4 menggunakan Schema, bukan Form
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ImprovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'improvements';

    protected static ?string $title = 'Work Order Improvements';

    protected static ?string $recordTitleAttribute = 'description';

    // PERBAIKAN v4: Gunakan Schema $schema
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // Atau ->schema() tergantung sub-versi, tapi components() biasanya aman
                Section::make('Improvement Details')
                    ->components([
                        Select::make('improvement_type')
                            ->label('Improvement Type')
                            ->options([
                                'process_optimization' => 'Process Optimization',
                                'spare_part_standardization' => 'Spare Part Standardization',
                                'procedure_update' => 'Procedure Update',
                                'training_provided' => 'Training Provided',
                            ])
                            ->required()
                            ->helperText('Select the type of improvement made'),

                        Textarea::make('description')
                            ->label('Improvement Description')
                            ->required()
                            ->rows(4)
                            ->placeholder('Describe the improvement made to this work order')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Impact Measurement')
                    ->components([
                        TextInput::make('time_saved_minutes')
                            ->label('Time Saved (Minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('0')
                            ->helperText('Estimated time saved by this improvement'),

                        TextInput::make('cost_saved')
                            ->label('Cost Saved (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->helperText('Estimated cost savings from this improvement'),

                        Toggle::make('recurrence_prevented')
                            ->label('Recurrence Prevented')
                            ->helperText('Does this improvement prevent the issue from recurring?')
                            ->default(false),
                    ])->columns(3),

                Hidden::make('improved_by_gpid')
                    ->default(Auth::user()->gpid ?? null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('improvedBy.name')
                    ->label('Improved By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('improvement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'process_optimization' => 'success',
                        'spare_part_standardization' => 'warning',
                        'procedure_update' => 'info',
                        'training_provided' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'process_optimization' => 'Process Optimization',
                        'spare_part_standardization' => 'Spare Part Standardization',
                        'procedure_update' => 'Procedure Update',
                        'training_provided' => 'Training Provided',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('time_saved_minutes')
                    ->label('Time Saved')
                    ->suffix(' min')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('cost_saved')
                    ->label('Cost Saved')
                    ->money('IDR')
                    ->sortable(),

                IconColumn::make('recurrence_prevented')
                    ->label('Prevented Recurrence')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('improvement_type')
                    ->options([
                        'process_optimization' => 'Process Optimization',
                        'spare_part_standardization' => 'Spare Part Standardization',
                        'procedure_update' => 'Procedure Update',
                        'training_provided' => 'Training Provided',
                    ]),
                TernaryFilter::make('recurrence_prevented')
                    ->label('Recurrence Prevention')
                    ->placeholder('All improvements')
                    ->trueLabel('Prevented recurrence')
                    ->falseLabel('Did not prevent'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Improvement'),
            ])
            // PERBAIKAN v4: Ganti actions() menjadi recordActions()
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}