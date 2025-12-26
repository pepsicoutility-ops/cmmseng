<?php

namespace App\Services;

use Exception;
use App\Exports\DataExport;
use App\Models\WorkOrder;
use App\Models\PmExecution;
use App\Models\PmSchedule;
use App\Models\PmCompliance;
use App\Models\InventoryMovement;
use App\Models\Inventory;
use App\Models\Part;
use App\Models\EquipmentTrouble;
use App\Models\Abnormality;
use App\Models\Asset;
use App\Models\SubAsset;
use App\Models\Area;
use App\Models\SubArea;
use App\Models\User;
use App\Models\Kaizen;
use App\Models\RootCauseAnalysis;
use App\Models\UtilityConsumption;
use App\Models\ProductionRecord;
use App\Models\StockAlert;
use App\Models\CbmSchedule;
use App\Models\CbmExecution;
use App\Models\RunningHour;
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
            
        } catch (Exception $e) {
            // Security: Log detailed error internally, return generic message to user
            \Illuminate\Support\Facades\Log::error('AIExcelService error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return ['error' => 'Terjadi kesalahan saat membuat laporan. Silakan coba lagi.'];
        }
    }
    
    /**
     * Get report data based on type
     */
    protected static function getReportData(string $reportType, array $filters): array
    {
        return match($reportType) {
            // Work Order Management
            'work_orders' => self::getWorkOrdersData($filters),
            'pm_executions' => self::getPmExecutionsData($filters),
            'pm_schedules' => self::getPmSchedulesData($filters),
            'pm_compliance' => self::getPmComplianceData($filters),
            
            // Equipment & Assets
            'equipment_troubles' => self::getEquipmentTroublesData($filters),
            'abnormalities' => self::getAbnormalitiesData($filters),
            'assets' => self::getAssetsData($filters),
            'sub_assets' => self::getSubAssetsData($filters),
            'running_hours' => self::getRunningHoursData($filters),
            
            // Inventory & Parts
            'inventory_movements' => self::getInventoryMovementsData($filters),
            'inventory' => self::getInventoryData($filters),
            'parts' => self::getPartsData($filters),
            'stock_alerts' => self::getStockAlertsData($filters),
            
            // CBM (Condition Based Maintenance)
            'cbm_schedules' => self::getCbmSchedulesData($filters),
            'cbm_executions' => self::getCbmExecutionsData($filters),
            
            // Improvement & Analysis
            'kaizen' => self::getKaizenData($filters),
            'root_cause_analysis' => self::getRootCauseAnalysisData($filters),
            
            // Utility & Production
            'utility_consumption' => self::getUtilityConsumptionData($filters),
            'production_records' => self::getProductionRecordsData($filters),
            
            // Master Data
            'areas' => self::getAreasData($filters),
            'sub_areas' => self::getSubAreasData($filters),
            'users' => self::getUsersData($filters),
            
            // Checklists
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
     * Abnormalities report
     */
    protected static function getAbnormalitiesData(array $filters): array
    {
        $query = Abnormality::with(['subAsset.asset', 'reportedByUser', 'assignedToUser', 'fixer', 'verifier']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'created_at');
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $abnormalities = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $abnormalities->map(fn($ab) => [
            $ab->id,
            $ab->subAsset?->asset?->name ?? '-',
            $ab->subAsset?->name ?? '-',
            $ab->abnormality_type,
            $ab->description,
            $ab->severity,
            $ab->status,
            $ab->reportedByUser?->name ?? '-',
            $ab->assignedToUser?->name ?? '-',
            $ab->deadline?->format('d-m-Y') ?? '-',
            $ab->fix_description ?? '-',
            $ab->fixer?->name ?? '-',
            $ab->fixed_at?->format('d-m-Y H:i') ?? '-',
            $ab->verifier?->name ?? '-',
            $ab->verified_at?->format('d-m-Y H:i') ?? '-',
            $ab->created_at->format('d-m-Y H:i'),
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Equipment', 'Component', 'Type', 'Description', 'Severity', 'Status', 'Reported By', 'Assigned To', 'Deadline', 'Fix Description', 'Fixed By', 'Fixed At', 'Verified By', 'Verified At', 'Created At'],
            'title' => 'Abnormalities Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * PM Schedules report
     */
    protected static function getPmSchedulesData(array $filters): array
    {
        $query = PmSchedule::with(['subAsset.asset', 'assignedTo']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $schedules = $query->orderBy('next_due_date', 'asc')->get();
        
        $rows = $schedules->map(fn($pm) => [
            $pm->id,
            $pm->subAsset?->asset?->name ?? '-',
            $pm->subAsset?->name ?? '-',
            $pm->task_name,
            $pm->frequency,
            $pm->interval_days,
            $pm->next_due_date?->format('d-m-Y') ?? '-',
            $pm->last_executed_at?->format('d-m-Y') ?? '-',
            $pm->assignedTo?->name ?? '-',
            $pm->status,
            $pm->priority,
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Equipment', 'Component', 'Task Name', 'Frequency', 'Interval (days)', 'Next Due', 'Last Executed', 'Assigned To', 'Status', 'Priority'],
            'title' => 'PM Schedules Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * PM Compliance report
     */
    protected static function getPmComplianceData(array $filters): array
    {
        $query = PmCompliance::query();
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'month_year');
        }
        
        $compliance = $query->orderBy('month_year', 'desc')->get();
        
        $rows = $compliance->map(fn($c) => [
            $c->month_year?->format('M Y') ?? '-',
            $c->department ?? '-',
            $c->total_scheduled,
            $c->total_completed,
            $c->total_overdue,
            number_format($c->compliance_percentage, 2) . '%',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Month', 'Department', 'Total Scheduled', 'Total Completed', 'Total Overdue', 'Compliance %'],
            'title' => 'PM Compliance Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Assets report
     */
    protected static function getAssetsData(array $filters): array
    {
        $query = Asset::with(['subArea.area']);
        
        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        
        $assets = $query->orderBy('name')->get();
        
        $rows = $assets->map(fn($a) => [
            $a->id,
            $a->code,
            $a->name,
            $a->subArea?->area?->name ?? '-',
            $a->subArea?->name ?? '-',
            $a->model ?? '-',
            $a->serial_number ?? '-',
            $a->manufacturer ?? '-',
            $a->installation_date?->format('d-m-Y') ?? '-',
            $a->is_active ? 'Active' : 'Inactive',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Code', 'Name', 'Area', 'Sub Area', 'Model', 'Serial Number', 'Manufacturer', 'Installation Date', 'Status'],
            'title' => 'Assets Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Sub Assets report
     */
    protected static function getSubAssetsData(array $filters): array
    {
        $query = SubAsset::with(['asset.subArea.area']);
        
        $subAssets = $query->orderBy('name')->get();
        
        $rows = $subAssets->map(fn($sa) => [
            $sa->id,
            $sa->code ?? '-',
            $sa->name,
            $sa->asset?->name ?? '-',
            $sa->asset?->subArea?->area?->name ?? '-',
            $sa->asset?->subArea?->name ?? '-',
            $sa->description ?? '-',
            $sa->is_active ? 'Active' : 'Inactive',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Code', 'Name', 'Parent Asset', 'Area', 'Sub Area', 'Description', 'Status'],
            'title' => 'Sub Assets Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Running Hours report
     */
    protected static function getRunningHoursData(array $filters): array
    {
        $query = RunningHour::with(['subAsset.asset']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'recorded_at');
        }
        
        $runningHours = $query->orderBy('recorded_at', 'desc')->get();
        
        $rows = $runningHours->map(fn($rh) => [
            $rh->subAsset?->asset?->name ?? '-',
            $rh->subAsset?->name ?? '-',
            $rh->running_hours,
            $rh->recorded_at?->format('d-m-Y H:i') ?? '-',
            $rh->notes ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Equipment', 'Component', 'Running Hours', 'Recorded At', 'Notes'],
            'title' => 'Running Hours Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Inventory report
     */
    protected static function getInventoryData(array $filters): array
    {
        $query = Inventory::with(['part']);
        
        $inventory = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $inventory->map(fn($inv) => [
            $inv->part?->part_number ?? '-',
            $inv->part?->name ?? '-',
            $inv->location ?? '-',
            $inv->quantity,
            $inv->part?->unit ?? '-',
            $inv->part?->unit_price ?? 0,
            ($inv->quantity * ($inv->part?->unit_price ?? 0)),
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Part Number', 'Part Name', 'Location', 'Quantity', 'Unit', 'Unit Price', 'Total Value'],
            'title' => 'Inventory Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Parts report
     */
    protected static function getPartsData(array $filters): array
    {
        $query = Part::withCount('inventories');
        
        $parts = $query->orderBy('name')->get();
        
        $rows = $parts->map(fn($p) => [
            $p->id,
            $p->part_number,
            $p->name,
            $p->description ?? '-',
            $p->category ?? '-',
            $p->unit,
            $p->unit_price ?? 0,
            $p->current_stock,
            $p->minimum_stock,
            $p->maximum_stock ?? '-',
            $p->lead_time_days ?? '-',
            $p->supplier ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Part Number', 'Name', 'Description', 'Category', 'Unit', 'Unit Price', 'Current Stock', 'Min Stock', 'Max Stock', 'Lead Time (days)', 'Supplier'],
            'title' => 'Parts Master Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Stock Alerts report
     */
    protected static function getStockAlertsData(array $filters): array
    {
        $query = StockAlert::with(['part']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $alerts = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $alerts->map(fn($a) => [
            $a->part?->part_number ?? '-',
            $a->part?->name ?? '-',
            $a->alert_type,
            $a->current_stock,
            $a->threshold,
            $a->status,
            $a->created_at->format('d-m-Y H:i'),
            $a->resolved_at?->format('d-m-Y H:i') ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Part Number', 'Part Name', 'Alert Type', 'Current Stock', 'Threshold', 'Status', 'Created At', 'Resolved At'],
            'title' => 'Stock Alerts Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * CBM Schedules report
     */
    protected static function getCbmSchedulesData(array $filters): array
    {
        $query = CbmSchedule::with(['subAsset.asset']);
        
        $schedules = $query->orderBy('next_check_date', 'asc')->get();
        
        $rows = $schedules->map(fn($cbm) => [
            $cbm->id,
            $cbm->subAsset?->asset?->name ?? '-',
            $cbm->subAsset?->name ?? '-',
            $cbm->parameter_name,
            $cbm->check_interval_days,
            $cbm->next_check_date?->format('d-m-Y') ?? '-',
            $cbm->last_checked_at?->format('d-m-Y') ?? '-',
            $cbm->status,
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Equipment', 'Component', 'Parameter', 'Interval (days)', 'Next Check', 'Last Checked', 'Status'],
            'title' => 'CBM Schedules Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * CBM Executions report
     */
    protected static function getCbmExecutionsData(array $filters): array
    {
        $query = CbmExecution::with(['cbmSchedule.subAsset.asset', 'executedBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'executed_at');
        }
        
        $executions = $query->orderBy('executed_at', 'desc')->get();
        
        $rows = $executions->map(fn($exec) => [
            $exec->cbmSchedule?->subAsset?->asset?->name ?? '-',
            $exec->cbmSchedule?->subAsset?->name ?? '-',
            $exec->cbmSchedule?->parameter_name ?? '-',
            $exec->measured_value,
            $exec->condition_status,
            $exec->executedBy?->name ?? '-',
            $exec->executed_at?->format('d-m-Y H:i') ?? '-',
            $exec->notes ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Equipment', 'Component', 'Parameter', 'Measured Value', 'Condition', 'Executed By', 'Executed At', 'Notes'],
            'title' => 'CBM Executions Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Kaizen report
     */
    protected static function getKaizenData(array $filters): array
    {
        $query = Kaizen::with(['submittedBy', 'reviewedBy', 'approvedBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'created_at');
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $kaizens = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $kaizens->map(fn($k) => [
            $k->id,
            $k->title,
            $k->category,
            $k->current_condition,
            $k->proposed_improvement,
            $k->expected_benefit,
            $k->status,
            $k->submittedBy?->name ?? '-',
            $k->reviewedBy?->name ?? '-',
            $k->approvedBy?->name ?? '-',
            $k->created_at->format('d-m-Y H:i'),
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Title', 'Category', 'Current Condition', 'Proposed Improvement', 'Expected Benefit', 'Status', 'Submitted By', 'Reviewed By', 'Approved By', 'Created At'],
            'title' => 'Kaizen Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Root Cause Analysis report
     */
    protected static function getRootCauseAnalysisData(array $filters): array
    {
        $query = RootCauseAnalysis::with(['workOrder', 'createdBy']);
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'created_at');
        }
        
        $rcas = $query->orderBy('created_at', 'desc')->get();
        
        $rows = $rcas->map(fn($rca) => [
            $rca->id,
            $rca->workOrder?->wo_number ?? '-',
            $rca->problem_statement,
            $rca->why_1 ?? '-',
            $rca->why_2 ?? '-',
            $rca->why_3 ?? '-',
            $rca->why_4 ?? '-',
            $rca->why_5 ?? '-',
            $rca->root_cause,
            $rca->corrective_action,
            $rca->preventive_action ?? '-',
            $rca->createdBy?->name ?? '-',
            $rca->created_at->format('d-m-Y H:i'),
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'WO Number', 'Problem Statement', 'Why 1', 'Why 2', 'Why 3', 'Why 4', 'Why 5', 'Root Cause', 'Corrective Action', 'Preventive Action', 'Created By', 'Created At'],
            'title' => 'Root Cause Analysis Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Utility Consumption report
     */
    protected static function getUtilityConsumptionData(array $filters): array
    {
        $query = UtilityConsumption::query();
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'record_date');
        }
        
        $consumptions = $query->orderBy('record_date', 'desc')->get();
        
        $rows = $consumptions->map(fn($uc) => [
            $uc->record_date?->format('d-m-Y') ?? '-',
            $uc->shift ?? '-',
            $uc->electricity_kwh ?? 0,
            $uc->water_m3 ?? 0,
            $uc->gas_m3 ?? 0,
            $uc->steam_kg ?? 0,
            $uc->compressed_air_m3 ?? 0,
            $uc->notes ?? '-',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Date', 'Shift', 'Electricity (kWh)', 'Water (m³)', 'Gas (m³)', 'Steam (kg)', 'Compressed Air (m³)', 'Notes'],
            'title' => 'Utility Consumption Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Production Records report
     */
    protected static function getProductionRecordsData(array $filters): array
    {
        $query = ProductionRecord::query();
        
        if (!empty($filters['period'])) {
            $query = self::applyPeriodFilter($query, $filters['period'], 'production_date');
        }
        
        $records = $query->orderBy('production_date', 'desc')->get();
        
        $rows = $records->map(fn($pr) => [
            $pr->production_date?->format('d-m-Y') ?? '-',
            $pr->shift ?? '-',
            $pr->line ?? '-',
            $pr->product_name ?? '-',
            $pr->planned_qty ?? 0,
            $pr->actual_qty ?? 0,
            $pr->good_qty ?? 0,
            $pr->reject_qty ?? 0,
            number_format(($pr->good_qty / max($pr->actual_qty, 1)) * 100, 2) . '%',
            $pr->downtime_minutes ?? 0,
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['Date', 'Shift', 'Line', 'Product', 'Planned Qty', 'Actual Qty', 'Good Qty', 'Reject Qty', 'Yield %', 'Downtime (min)'],
            'title' => 'Production Records Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Areas report
     */
    protected static function getAreasData(array $filters): array
    {
        $query = Area::withCount('subAreas');
        
        $areas = $query->orderBy('name')->get();
        
        $rows = $areas->map(fn($a) => [
            $a->id,
            $a->name,
            $a->code ?? '-',
            $a->description ?? '-',
            $a->sub_areas_count,
            $a->is_active ? 'Active' : 'Inactive',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Name', 'Code', 'Description', 'Sub Areas Count', 'Status'],
            'title' => 'Areas Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Sub Areas report
     */
    protected static function getSubAreasData(array $filters): array
    {
        $query = SubArea::with(['area'])->withCount('assets');
        
        $subAreas = $query->orderBy('name')->get();
        
        $rows = $subAreas->map(fn($sa) => [
            $sa->id,
            $sa->name,
            $sa->code ?? '-',
            $sa->area?->name ?? '-',
            $sa->description ?? '-',
            $sa->assets_count,
            $sa->is_active ? 'Active' : 'Inactive',
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'Name', 'Code', 'Area', 'Description', 'Assets Count', 'Status'],
            'title' => 'Sub Areas Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Users report
     */
    protected static function getUsersData(array $filters): array
    {
        $query = User::query();
        
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        
        $users = $query->orderBy('name')->get();
        
        $rows = $users->map(fn($u) => [
            $u->id,
            $u->gpid,
            $u->name,
            $u->email,
            $u->role,
            $u->department ?? '-',
            $u->position ?? '-',
            $u->phone ?? '-',
            $u->is_active ? 'Active' : 'Inactive',
            $u->created_at->format('d-m-Y'),
        ]);
        
        return [
            'rows' => $rows,
            'headings' => ['ID', 'GPID', 'Name', 'Email', 'Role', 'Department', 'Position', 'Phone', 'Status', 'Created At'],
            'title' => 'Users Report',
            'total_rows' => $rows->count(),
        ];
    }
    
    /**
     * Checklist data report - Export ALL columns from table
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
        
        // Get all fillable columns from model + created_at
        $modelInstance = new $model;
        $fillableColumns = $modelInstance->getFillable();
        
        // Build headings from column names (convert snake_case to Title Case)
        $headings = array_merge(
            ['ID', 'Created At'],
            array_map(fn($col) => ucwords(str_replace('_', ' ', $col)), $fillableColumns)
        );
        
        // Map all data including all fillable columns
        $rows = $checklists->map(function($record) use ($fillableColumns) {
            $baseData = [
                $record->id,
                $record->created_at->format('d-m-Y H:i'),
            ];
            
            // Get all fillable column values
            $columnData = array_map(fn($col) => $record->{$col} ?? '-', $fillableColumns);
            
            return array_merge($baseData, $columnData);
        });
        
        return [
            'rows' => $rows,
            'headings' => $headings,
            'title' => strtoupper(str_replace('_', ' ', $equipmentType)) . ' Checklist Report',
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
