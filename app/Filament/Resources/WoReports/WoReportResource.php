<?php

namespace App\Filament\Resources\WoReports;

use App\Filament\Resources\WoReports\Pages\ManageWoReports;
use App\Models\WorkOrder;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WoReportResource extends Resource
{
    protected static ?string $model = WorkOrder::class;
    
    protected static ?string $navigationLabel = 'WO Reports';
    
    protected static UnitEnum|string|null $navigationGroup = 'Reports & Analytics';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['asset', 'cost']))
            ->columns([
                TextColumn::make('wo_number')
                    ->label('WO Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('operator_name')
                    ->label('Operator')
                    ->searchable(),
                TextColumn::make('shift')
                    ->badge()
                    ->formatStateUsing(fn ($state) => "Shift {$state}"),
                TextColumn::make('problem_type')
                    ->label('Problem Type')
                    ->badge()
                    ->colors([
                        'danger' => 'breakdown',
                        'warning' => 'abnormality',
                        'primary' => 'request_consumable',
                        'success' => 'improvement',
                        'info' => 'inspection',
                    ]),
                TextColumn::make('asset.name')
                    ->label('Equipment')
                    ->searchable(),
                TextColumn::make('assign_to')
                    ->label('Assigned To')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => ['submitted', 'closed'],
                        'info' => 'reviewed',
                        'primary' => 'approved',
                        'warning' => 'in_progress',
                        'danger' => 'on_hold',
                        'success' => 'completed',
                    ]),
                TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),
                TextColumn::make('total_downtime')
                    ->label('Downtime')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : '-')
                    ->sortable(),
                TextColumn::make('mttr')
                    ->label('MTTR')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : '-')
                    ->sortable(),
                TextColumn::make('cost.total_cost')
                    ->label('Total Cost')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
                SelectFilter::make('problem_type')
                    ->options([
                        'abnormality' => 'Abnormality',
                        'breakdown' => 'Breakdown',
                        'request_consumable' => 'Request Consumable',
                        'improvement' => 'Improvement',
                        'inspection' => 'Inspection',
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->multiple(),
                SelectFilter::make('assign_to')
                    ->options([
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                    ])
                    ->multiple(),
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'in_progress' => 'In Progress',
                        'on_hold' => 'On Hold',
                        'completed' => 'Completed',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('WO_Report_' . date('Y-m-d_His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
                    ]),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('WO_Report_Selected_' . date('Y-m-d_His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
                    ]),
            ]);
    }
    
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWoReports::route('/'),
        ];
    }
}
