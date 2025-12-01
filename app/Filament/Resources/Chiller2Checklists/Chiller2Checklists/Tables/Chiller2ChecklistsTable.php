<?php

namespace App\Filament\Resources\Chiller2Checklists\Chiller2Checklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Chiller2ChecklistsTable
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
                TextColumn::make('sat_evap_t')
                    ->label('Sat. Evap. T')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sat_dis_t')
                    ->label('Sat. Dis. T')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fla')
                    ->label('FLA')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('motor_amps')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('run_hours')
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
