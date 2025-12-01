<?php

namespace App\Filament\Resources\AhuChecklists\AhuChecklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AhuChecklistsTable
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
                TextColumn::make('ahu_mb_1_1_hf')
                    ->label('AHU MB-1.1: HF')
                    ->toggleable(),
                TextColumn::make('pau_mb_1_pf')
                    ->label('PAU MB-1: PF')
                    ->toggleable(),
                TextColumn::make('ahu_vrf_mb_ms_1a_pf')
                    ->label('VRF MB-MS-1A: PF')
                    ->toggleable(),
                TextColumn::make('if_pre_filter_a')
                    ->label('IF Pre Filter A')
                    ->toggleable(),
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

