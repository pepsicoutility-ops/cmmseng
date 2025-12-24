<?php

namespace App\Services;

use App\Exports\DataExport;
use App\Models\WorkOrder;
use App\Models\PmExecution;
use App\Models\InventoryMovement;
use App\Models\EquipmentTrouble;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\AhuChecklist;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AIExcelService
{
    /**
     * Generate Excel report and return download URL
     */
    public static function generateReport(string $reportType, array $filters = []): array
    {
        try {
            $data = self::getReportData($reportType, $filters);
            
            if (empty($data['rows'])) {
                return ['error' => 'Tidak ada data untuk diekspor'];
            }
            
            $filename = self::generateFilename($reportType, $filters);
            $filePath = 'exports/' . $filename;
            
            // Generate Excel file
            Excel::store(
                new DataExport($data['rows'], $data['headings'], $data['title']),
                $filePath,
                'public'
            );
            
            // Generate download URL
            $url = url('storage/' . $filePath);
            
            return [
                'success' => true,
                'filename' => $filename,
                'download_url' => $url,
                'file_path' => $filePath,
                'total_rows' => count($data['rows']),
                'message' => "File Excel berhasil dibuat dengan {$data['total_rows']} baris data",
            ];
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get report data based on type
     */
    protected static function getReportData(string $reportType, array $filters): array
    {
        return match($reportType) {
            'work_orders' => self::getWorkOrdersData($filters),
            'pm_executions' => self::getPmExecutionsData($filters),
            'inventory_movements' => self::getInventoryMovementsData($filters),
            'equipment_troubles' => self::getEquipmentTroublesData($filters),
            'compressor1_checklist' => self::getChecklistData('compressor1', $filters),
            'compressor2_checklist' => self::getChecklistData('compressor2', $filters),
            'chiller1_checklist' => self::getChecklistData('chiller1', $filters),
            'chiller2_checklist' => self::getChecklistData('chiller2', $filters),
            'ahu_checklist' => self::getChecklistData('ahu', $filters),
            default => ['rows' => [], 'headings' => [], 'title' => 'Unknown Report'],
        };
    }
    
    /**
     * Work Orders report
     */
    protected static function getWorkOrdersData(array $filters): array
    {
        $query = WorkOrder::with(['subAsset.asset', 'createdBy']);
        
        // Apply date filters
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        $workOrders = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $workOrders->map(fn($wo) => [
            $wo->wo_number,
            $wo->subAsset?->asset?->name ?? '-',
            $wo->subAsset?->name ?? '-',
            $wo->type,
            $wo->priority,
            $wo->status,
            $wo->problem_description,
            $wo->createdBy?->name ?? '-',
            $wo->created_at->format('d-m-Y H:i'),
            $wo->completed_at?->format('d-m-Y H:i') ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['WO Number', 'Equipment', 'Component', 'Type', 'Priority', 'Status', 'Problem', 'Created By', 'Created At', 'Completed At'],
            'title' => 'Work Orders Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * PM Executions report
     */
    protected static function getPmExecutionsData(array $filters): array
    {
        $query = PmExecution::with(['pmSchedule.subAsset.asset', 'executedBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'created_at');
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $executions = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $executions->map(fn($pm) => [
            $pm->pmSchedule?->subAsset?->asset?->name ?? '-',
            $pm->pmSchedule?->subAsset?->name ?? '-',
            $pm->created_at->format('d-m-Y'),
            $pm->status,
            $pm->executedBy?->name ?? '-',
            $pm->findings ?? '-',
            $pm->recommendations ?? '-',
            $pm->downtime_minutes ?? 0,
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Equipment', 'Component', 'Execution Date', 'Status', 'Executed By', 'Findings', 'Recommendations', 'Downtime (min)'],
            'title' => 'PM Executions Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Inventory Movements report
     */
    protected static function getInventoryMovementsData(array $filters): array
    {
        $query = InventoryMovement::with(['part', 'performedBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'movement_date');
        }
        
        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }
        
        $movements = $query->orderBy('movement_date', 'desc')->get();
        
        $rows = $movements->map(fn($mov) => [
            $mov->movement_date->format('d-m-Y'),
            $mov->part?->part_number ?? '-',
            $mov->part?->name ?? '-',
            $mov->movement_type,
            $mov->quantity,
            $mov->part?->unit ?? '-',
            $mov->unit_cost ?? 0,
            ($mov->quantity * ($mov->unit_cost ?? 0)),
            $mov->notes ?? '-',
            $mov->performedBy?->name ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Date', 'Part Number', 'Part Name', 'Type', 'Quantity', 'Unit', 'Unit Cost', 'Total Cost', 'Notes', 'Performed By'],
            'title' => 'Inventory Movements Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Equipment Troubles report
     */
    protected static function getEquipmentTroublesData(array $filters): array
    {
        $query = EquipmentTrouble::with(['subAsset.asset', 'reportedBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'trouble_date');
        }
        
        if (!empty($filters['equipment_name'])) {
            $query->whereHas('subAsset.asset', fn($q) => 
                $q->where('name', 'like', "%{$filters['equipment_name']}%")
            );
        }
        
        $troubles = $query->orderBy('trouble_date', 'desc')->get();
        
        $rows = $troubles->map(fn($tr) => [
            $tr->trouble_date->format('d-m-Y'),
            $tr->subAsset?->asset?->name ?? '-',
            $tr->subAsset?->name ?? '-',
            $tr->issue_description,
            $tr->action_taken ?? '-',
            $tr->downtime_minutes ?? 0,
            $tr->reportedBy?->name ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Date', 'Equipment', 'Component', 'Issue Description', 'Action Taken', 'Downtime (min)', 'Reported By'],
            'title' => 'Equipment Troubles Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Checklist data report
     */
    protected static function getChecklistData(string $equipmentType, array $filters): array
    {
        $model = match($equipmentType) {
            'compressor1' => Compressor1Checklist::class,
            'compressor2' => Compressor2Checklist::class,
            'chiller1' => Chiller1Checklist::class,
            'chiller2' => Chiller2Checklist::class,
            'ahu' => AhuChecklist::class,
        };
        
        $query = $model::query();
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'created_at');
        }
        
        if (!empty($filters['shift'])) {
            $query->where('shift', $filters['shift']);
        }
        
        $checklists = $query->orderBy('created_at', 'desc')->get();
        
        // Dynamic columns based on equipment type
        $rows = $checklists->map(function($record) use ($equipmentType) {
            $baseData = [
                $record->created_at->format('d-m-Y H:i'),
                $record->shift,
                $record->operator_name ?? '-',
            ];
            
            // Add equipment-specific columns
            $specificData = match($equipmentType) {
                'compressor1', 'compressor2' => [
                    $record->tot_run_hours ?? '-',
                    $record->discharge_temperature ?? '-',
                    $record->discharge_pressure ?? '-',
                    $record->bearing_oil_temperature ?? '-',
                    $record->bearing_oil_pressure ?? '-',
                    $record->cws_temperature ?? '-',
                    $record->cwr_temperature ?? '-',
                ],
                'chiller1', 'chiller2' => [
                    $record->run_hours ?? '-',
                    $record->sat_evap_t ?? '-',
                    $record->sat_dis_t ?? '-',
                    $record->evap_p ?? '-',
                    $record->conds_p ?? '-',
                    $record->motor_amps ?? '-',
                    $record->motor_volts ?? '-',
                ],
                'ahu' => [
                    $record->run_hours ?? '-',
                    $record->filter_condition ?? '-',
                    $record->blower_condition ?? '-',
                    $record->vibration_check ?? '-',
                    $record->temperature_in ?? '-',
                    $record->temperature_out ?? '-',
                ],
            };
            
            return array_merge($baseData, $specificData);
        });
        
        $headings = match($equipmentType) {
            'compressor1', 'compressor2' => 
                ['Date', 'Shift', 'Operator', 'Run Hours', 'Discharge Temp', 'Discharge Press', 'Bearing Oil Temp', 'Bearing Oil Press', 'CWS Temp', 'CWR Temp'],
            'chiller1', 'chiller2' => 
                ['Date', 'Shift', 'Operator', 'Run Hours', 'SAT Evap T', 'SAT Dis T', 'Evap P', 'Conds P', 'Motor Amps', 'Motor Volts'],
            'ahu' => 
                ['Date', 'Shift', 'Operator', 'Run Hours', 'Filter Condition', 'Blower Condition', 'Vibration', 'Temp In', 'Temp Out'],
        };
        
        return [
            'rows' => $rows,
            'headings' => $headings,
            'title' => strtoupper($equipmentType) . ' Checklist Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Apply period filter to query
     */
    protected static function applyPeriodFilter($query, string $period, string $dateColumn = 'created_at')
    {
        return match($period) {
            'today' => $query->whereDate($dateColumn, today()),
            'yesterday' => $query->whereDate($dateColumn, today()->subDay()),
            'this_week' => $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween($dateColumn, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year),
            'last_month' => $query->whereMonth($dateColumn, now()->subMonth()->month)->whereYear($dateColumn, now()->subMonth()->year),
            'this_quarter' => $query->whereBetween($dateColumn, [now()->startOfQuarter(), now()->endOfQuarter()]),
            'this_year' => $query->whereYear($dateColumn, now()->year),
            'last_year' => $query->whereYear($dateColumn, now()->subYear()->year),
            default => $query->where($dateColumn, '>=', now()->subMonth()),
        };
    }
    
    /**
     * Generate filename for export
     */
    protected static function generateFilename(string $reportType, array $filters): string
    {
        $timestamp = now()->format('Ymd_His');
        $period = $filters['period'] ?? 'all';
        
        return "{$reportType}_{$period}_{$timestamp}.xlsx";
    }
}
