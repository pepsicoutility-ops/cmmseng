<?php

namespace App\Filament\Resources\Compressor2Checklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Compressor2ChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->poll('30s')
            ->columns([
                TextColumn::make('shift')
                    ->badge(),
                TextColumn::make('gpid')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('tot_run_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bearing_oil_temperature')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bearing_oil_pressure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discharge_pressure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discharge_temperature')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cws_temperature')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cwr_temperature')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cws_pressure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cwr_pressure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('refrigerant_pressure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dew_point')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
