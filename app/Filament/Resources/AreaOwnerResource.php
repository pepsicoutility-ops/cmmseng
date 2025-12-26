<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AreaOwnerResource\Pages\ListAreaOwners;
use App\Filament\Resources\AreaOwnerResource\Pages\CreateAreaOwner;
use App\Filament\Resources\AreaOwnerResource\Pages\EditAreaOwner;
use App\Filament\Resources\AreaOwnerResource\Pages;
use App\Filament\Resources\AreaOwnerResource\Schemas\AreaOwnerForm;
use App\Models\AreaOwner;
use App\Models\Area;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Traits\HasRoleBasedAccess;

class AreaOwnerResource extends Resource
{
    use HasRoleBasedAccess;
    
    protected static ?string $model = AreaOwner::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Area Owners';

    protected static string | \UnitEnum | null $navigationGroup = 'Performance KPIs';

    protected static ?int $navigationSort = 2;

    /**
     * Operator role can only access Work Orders
     */
    public static function canAccess(): bool
    {
        return static::canAccessExcludeOperator();
    }

    public static function form(Schema $schema): Schema
    {
        return AreaOwnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lines_names')
                    ->label('Lines')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->lines_names),

                TextColumn::make('owner.name')
                    ->label('Owner Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.gpid')
                    ->label('GPID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'technician' => 'info',
                        'engineer' => 'success',
                        'supervisor' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '-')),

                TextColumn::make('assigned_date')
                    ->label('Assigned Date')
                    ->date('d/m/Y')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('area.name')
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(Area::pluck('name', 'id'))
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All owners')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (AreaOwner $record) => $record->update(['is_active' => false]))
                    ->visible(fn (AreaOwner $record) => $record->is_active),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (AreaOwner $record) => $record->update(['is_active' => true]))
                    ->visible(fn (AreaOwner $record) => !$record->is_active),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAreaOwners::route('/'),
            'create' => CreateAreaOwner::route('/create'),
            'edit' => EditAreaOwner::route('/{record}/edit'),
        ];
    }
}
