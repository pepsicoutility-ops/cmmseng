<?php

namespace App\Filament\Resources\PmReports;

use Filament\Forms\Components\DatePicker;
use Maatwebsite\Excel\Excel;
use App\Filament\Resources\PmReports\Pages\ManagePmReports;
use App\Models\PmExecution;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Traits\HasRoleBasedAccess;
use Illuminate\Support\Facades\DB;

class PmReportResource extends Resource
{
    use HasRoleBasedAccess;
    protected static ?string $model = PmExecution::class;
    
    protected static ?string $navigationLabel = 'PM Reports';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Reports & Analytics';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentChartBar;
    
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return static::canAccessManagement();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['pmSchedule.asset', 'executedBy', 'cost']))
            ->columns([
                TextColumn::make('pmSchedule.code')
                    ->label('PM Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pmSchedule.title')
                    ->label('PM Title')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('pmSchedule.asset.name')
                    ->label('Equipment')
                    ->searchable(),
                TextColumn::make('executedBy.name')
                    ->label('Executed By')
                    ->searchable(),
                TextColumn::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('actual_start')
                    ->label('Actual Start')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : '-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('compliance_status')
                    ->label('Compliance')
                    ->badge()
                    ->colors([
                        'success' => 'on_time',
                        'warning' => 'late',
                        'danger' => 'very_late',
                    ]),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'in_progress',
                        'success' => 'completed',
                    ]),
                TextColumn::make('cost.total_cost')
                    ->label('Total Cost')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('scheduled_date')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('scheduled_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('scheduled_date', '<=', $date));
                    }),
                SelectFilter::make('department')
                    ->label('Department')
                    ->options([
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                    ])
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            $query->whereHas('pmSchedule.asset', fn ($q) => $q->where('department', $data['value']));
                        }
                    }),
                SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
                SelectFilter::make('compliance_status')
                    ->label('Compliance Status')
                    ->options([
                        'on_time' => 'On Time',
                        'late' => 'Late',
                        'very_late' => 'Very Late',
                    ]),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->recordActions([])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('PM_Report_' . date('Y-m-d_His'))
                            ->withWriterType(Excel::XLSX),
                    ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('PM_Report_Selected_' . date('Y-m-d_His'))
                            ->withWriterType(Excel::XLSX),
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
            'index' => ManagePmReports::route('/'),
        ];
    }
}
