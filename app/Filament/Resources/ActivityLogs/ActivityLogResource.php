<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    
    protected static ?string $navigationLabel = 'Activity Logs';
    
    protected static UnitEnum|string|null $navigationGroup = 'System Management';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    
    protected static ?int $navigationSort = 2;
    
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'super_admin';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user_gpid ? "GPID: {$record->user_gpid}" : null),
                TextColumn::make('user_role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'manager',
                        'success' => 'asisten_manager',
                        'info' => 'technician',
                        'gray' => ['tech_store', 'operator'],
                    ])
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->colors([
                        'success' => 'created',
                        'info' => ['updated', 'viewed'],
                        'danger' => 'deleted',
                        'warning' => ['login', 'logout'],
                    ])
                    ->sortable()
                    ->searchable(),
                TextColumn::make('model')
                    ->label('Module')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('model_id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'viewed' => 'Viewed',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ])
                    ->multiple(),
                SelectFilter::make('user_role')
                    ->label('Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'manager' => 'Manager',
                        'asisten_manager' => 'Asisten Manager',
                        'technician' => 'Technician',
                        'tech_store' => 'Tech Store',
                        'operator' => 'Operator',
                    ])
                    ->multiple(),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->bulkActions([])
            ->poll('10s');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
