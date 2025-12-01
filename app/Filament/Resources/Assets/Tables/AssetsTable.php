<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Models\Area;
use App\Models\SubArea;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subArea.area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('subArea.name')
                    ->label('Sub Area')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('name')
                    ->label('Asset Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                TextColumn::make('installation_date')
                    ->label('Installed')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sub_assets_count')
                    ->label('Sub Assets')
                    ->counts('subAssets')
                    ->badge()
                    ->color('warning'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('area')
                    ->label('Area')
                    ->relationship('subArea.area', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sub_area_id')
                    ->label('Sub Area')
                    ->options(SubArea::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
