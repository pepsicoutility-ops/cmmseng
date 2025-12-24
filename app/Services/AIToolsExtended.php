<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Part;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\PmSchedule;
use App\Models\PmExecution;
use App\Models\PmCompliance;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WoCost;
use App\Models\PmCost;
use Illuminate\Support\Facades\DB;

class AIToolsExtended
{
    /**
     * Get extended tool definitions
     */
    public static function getExtendedToolDefinitions(): array
    {
        return [
            // Master Data Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_areas_list',
                    'description' => 'Mendapatkan daftar semua area produksi',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_parts',
                    'description' => 'Mencari spare parts berdasarkan nama atau part number',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Nama atau part number'],
                            'category' => ['type' => 'string', 'description' => 'Kategori part (optional)'],
                        ],
                        'required' => ['search'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_inventory_stock',
                    'description' => 'Mendapatkan stock level spare parts',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'part_name' => ['type' => 'string', 'description' => 'Nama part (optional)'],
                            'low_stock_only' => ['type' => 'boolean', 'description' => 'Hanya tampilkan stock rendah'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_stock_alerts',
                    'description' => 'Mendapatkan alert untuk stock yang rendah atau habis',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                        'required' => [],
                    ],
                ],
            ],
            
            // PM Management Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_pm_schedules',
                    'description' => 'Mendapatkan jadwal preventive maintenance',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => ['type' => 'string', 'description' => 'Filter by equipment'],
                            'status' => ['type' => 'string', 'description' => 'active, overdue, completed'],
                            'days_ahead' => ['type' => 'integer', 'description' => 'Hari ke depan (default: 30)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_pm_compliance',
                    'description' => 'Mendapatkan compliance rate PM (on-time vs late)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => ['type' => 'string', 'description' => 'month, quarter, year'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            
            // Advanced Work Order Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_wo_statistics',
                    'description' => 'Mendapatkan statistik work orders (by type, priority, status)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => ['type' => 'string', 'description' => 'week, month, quarter, year'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_maintenance_costs',
                    'description' => 'Mendapatkan biaya maintenance (PM + WO)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => ['type' => 'string', 'description' => 'Filter by equipment'],
                            'period' => ['type' => 'string', 'description' => 'month, quarter, year'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            
            // User & Technician Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_technician_workload',
                    'description' => 'Mendapatkan workload teknisi (assigned WOs)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'technician_name' => ['type' => 'string', 'description' => 'Nama teknisi (optional)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            
            // Analytics Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_downtime',
                    'description' => 'Mendapatkan data downtime equipment',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => ['type' => 'string', 'description' => 'Nama equipment'],
                            'days' => ['type' => 'integer', 'description' => 'Periode (default: 30)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_top_issues',
                    'description' => 'Mendapatkan top issues/masalah yang sering terjadi',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'Jumlah top issues (default: 10)'],
                            'period' => ['type' => 'string', 'description' => 'month, quarter, year'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_reliability',
                    'description' => 'Mendapatkan reliability metrics equipment (MTBF, MTTR)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => ['type' => 'string', 'description' => 'Nama equipment'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            
            // Database Query Function
            [
                'type' => 'function',
                'function' => [
                    'name' => 'query_database',
                    'description' => 'Menjalankan query SQL read-only untuk data kompleks yang tidak tersedia di function lain',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query_description' => ['type' => 'string', 'description' => 'Deskripsi data yang dicari'],
                        ],
                        'required' => ['query_description'],
                    ],
                ],
            ],
            
            // Excel Export Function
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_excel_report',
                    'description' => 'Generate dan download file Excel untuk rekapan data (work orders, PM, inventory, checklist, dll). Gunakan untuk permintaan export/download/rekapan data.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'report_type' => [
                                'type' => 'string',
                                'description' => 'Tipe report',
                                'enum' => ['work_orders', 'pm_executions', 'inventory_movements', 'equipment_troubles', 'compressor1_checklist', 'compressor2_checklist', 'chiller1_checklist', 'chiller2_checklist', 'ahu_checklist'],
                            ],
                            'period' => [
                                'type' => 'string',
                                'description' => 'Periode data',
                                'enum' => ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_quarter', 'this_year', 'last_year'],
                            ],
                            'status' => ['type' => 'string', 'description' => 'Filter status (optional)'],
                            'priority' => ['type' => 'string', 'description' => 'Filter priority (optional)'],
                            'shift' => ['type' => 'integer', 'description' => 'Filter shift untuk checklist (optional)'],
                        ],
                        'required' => ['report_type'],
                    ],
                ],
            ],
            
            // Analytics Functions
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_root_cause',
                    'description' => 'Menganalisa akar penyebab masalah equipment yang sering trouble. Identifikasi pattern, correlation, dan berikan rekomendasi action. Gunakan untuk pertanyaan "kenapa sering rusak", "akar masalah", "analisa trouble".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_id' => [
                                'type' => 'integer',
                                'description' => 'ID equipment yang akan dianalisa (wajib)',
                            ],
                            'analysis_period' => [
                                'type' => 'integer',
                                'description' => 'Periode analisa dalam hari (default: 90 hari)',
                                'enum' => [30, 60, 90, 180],
                            ],
                            'trouble_threshold' => [
                                'type' => 'integer',
                                'description' => 'Minimum jumlah trouble untuk analisa (default: 3)',
                            ],
                        ],
                        'required' => ['equipment_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_cost_optimization',
                    'description' => 'Menganalisa peluang penghematan biaya maintenance. Identifikasi cost drivers, calculate potential savings, dan berikan rekomendasi improvement. Gunakan untuk pertanyaan "gimana cara hemat biaya", "penghematan maintenance", "optimasi budget".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => [
                                'type' => 'integer',
                                'description' => 'Periode analisa dalam hari (default: 90 hari)',
                                'enum' => [30, 60, 90, 180],
                            ],
                            'cost_threshold' => [
                                'type' => 'integer',
                                'description' => 'Minimum biaya untuk dianalisa (default: 100000 Rupiah)',
                            ],
                            'include_opportunities' => [
                                'type' => 'boolean',
                                'description' => 'Include peluang penghematan detail (default: true)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'detect_anomalies',
                    'description' => 'Detect anomali atau pola abnormal di data checklist equipment. Identifikasi parameter yang keluar dari range normal, trending abnormal, atau berpotensi breakdown. Gunakan untuk pertanyaan "ada anomali tidak", "cek kondisi abnormal", "detect masalah equipment".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_type' => [
                                'type' => 'string',
                                'description' => 'Tipe equipment untuk analisa: compressor1, compressor2, chiller1, chiller2, ahu. Kosongkan untuk analisa semua equipment.',
                                'enum' => ['compressor1', 'compressor2', 'chiller1', 'chiller2', 'ahu'],
                            ],
                            'sensitivity' => [
                                'type' => 'string',
                                'description' => 'Sensitivitas deteksi: low (hanya extreme), medium (balanced), high (sensitif). Default: medium',
                                'enum' => ['low', 'medium', 'high'],
                            ],
                            'lookback_days' => [
                                'type' => 'integer',
                                'description' => 'Periode baseline untuk hitung normal range (default: 90 hari)',
                            ],
                            'recent_days' => [
                                'type' => 'integer',
                                'description' => 'Periode recent data untuk analisa (default: 7 hari)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute extended tool
     */
    public static function executeExtendedTool(string $functionName, array $arguments): array
    {
        try {
            return match ($functionName) {
                'get_areas_list' => self::getAreasList(),
                'search_parts' => self::searchParts($arguments['search'] ?? '', $arguments['category'] ?? null),
                'get_inventory_stock' => self::getInventoryStock($arguments['part_name'] ?? null, $arguments['low_stock_only'] ?? false),
                'get_stock_alerts' => self::getStockAlerts(),
                'get_pm_schedules' => self::getPmSchedules($arguments['equipment_name'] ?? null, $arguments['status'] ?? null, $arguments['days_ahead'] ?? 30),
                'get_pm_compliance' => self::getPmCompliance($arguments['period'] ?? 'month'),
                'get_wo_statistics' => self::getWoStatistics($arguments['period'] ?? 'month'),
                'get_maintenance_costs' => self::getMaintenanceCosts($arguments['equipment_name'] ?? null, $arguments['period'] ?? 'month'),
                'get_technician_workload' => self::getTechnicianWorkload($arguments['technician_name'] ?? null),
                'get_equipment_downtime' => self::getEquipmentDowntime($arguments['equipment_name'] ?? '', $arguments['days'] ?? 30),
                'get_top_issues' => self::getTopIssues($arguments['limit'] ?? 10, $arguments['period'] ?? 'month'),
                'get_equipment_reliability' => self::getEquipmentReliability($arguments['equipment_name'] ?? ''),
                'query_database' => self::queryDatabase($arguments['query_description'] ?? ''),
                'generate_excel_report' => AIExcelService::generateReport(
                    $arguments['report_type'] ?? '',
                    $arguments
                ),
                'analyze_root_cause' => (new AIAnalyticsService())->analyzeRootCause($arguments),
                'analyze_cost_optimization' => (new AIAnalyticsService())->analyzeCostOptimization($arguments),
                'detect_anomalies' => (new AIAnalyticsService())->detectAnomalies($arguments),
                default => ['error' => 'Function not found'],
            };
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Implementation methods
    protected static function getAreasList(): array
    {
        $areas = Area::with('subAreas')->where('is_active', 1)->get();
        
        return [
            'total_areas' => $areas->count(),
            'areas' => $areas->map(fn($area) => [
                'name' => $area->name,
                'code' => $area->code,
                'description' => $area->description,
                'sub_areas_count' => $area->subAreas->count(),
                'sub_areas' => $area->subAreas->pluck('name')->toArray(),
            ])->toArray(),
        ];
    }

    protected static function searchParts(string $search, ?string $category): array
    {
        $query = Part::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%");
            });
        }
        
        if ($category) {
            $query->where('category', 'like', "%{$category}%");
        }
        
        $parts = $query->limit(20)->get();
        
        return [
            'total_found' => $parts->count(),
            'parts' => $parts->map(fn($part) => [
                'part_number' => $part->part_number,
                'name' => $part->name,
                'description' => $part->description,
                'category' => $part->category,
                'unit' => $part->unit,
                'min_stock' => $part->min_stock,
                'max_stock' => $part->max_stock,
                'current_stock' => $part->current_stock,
                'unit_cost' => $part->unit_cost,
            ])->toArray(),
        ];
    }

    protected static function getInventoryStock(?string $partName, bool $lowStockOnly): array
    {
        $query = Part::query();
        
        if ($partName) {
            $query->where('name', 'like', "%{$partName}%");
        }
        
        if ($lowStockOnly) {
            $query->whereRaw('current_stock <= min_stock');
        }
        
        $parts = $query->orderBy('current_stock')->limit(50)->get();
        
        return [
            'total_parts' => $parts->count(),
            'low_stock_count' => $parts->filter(fn($p) => $p->current_stock <= $p->min_stock)->count(),
            'parts' => $parts->map(fn($part) => [
                'part_number' => $part->part_number,
                'name' => $part->name,
                'current_stock' => $part->current_stock,
                'min_stock' => $part->min_stock,
                'max_stock' => $part->max_stock,
                'unit' => $part->unit,
                'status' => $part->current_stock <= $part->min_stock ? 'LOW' : 'OK',
            ])->toArray(),
        ];
    }

    protected static function getStockAlerts(): array
    {
        $alerts = StockAlert::with('part')
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        return [
            'total_alerts' => $alerts->count(),
            'alerts' => $alerts->map(fn($alert) => [
                'part' => $alert->part->name,
                'part_number' => $alert->part->part_number,
                'alert_type' => $alert->alert_type,
                'current_stock' => $alert->current_stock,
                'threshold' => $alert->threshold,
                'date' => $alert->created_at->format('d-m-Y H:i'),
            ])->toArray(),
        ];
    }

    protected static function getPmSchedules(?string $equipmentName, ?string $status, int $daysAhead): array
    {
        $query = PmSchedule::with(['subAsset.asset']);
        
        if ($equipmentName) {
            $query->whereHas('subAsset.asset', fn($q) => $q->where('name', 'like', "%{$equipmentName}%"));
        }
        
        if ($status === 'active') {
            $query->where('is_active', true);
        }
        
        $schedules = $query->limit(20)->get();
        
        return [
            'total_schedules' => $schedules->count(),
            'schedules' => $schedules->map(fn($schedule) => [
                'equipment' => $schedule->subAsset?->asset?->name,
                'component' => $schedule->subAsset?->name,
                'frequency' => $schedule->frequency,
                'frequency_value' => $schedule->frequency_value,
                'last_execution' => $schedule->last_execution_date?->format('d-m-Y'),
                'next_due' => $schedule->next_due_date?->format('d-m-Y'),
                'status' => $schedule->is_active ? 'Active' : 'Inactive',
            ])->toArray(),
        ];
    }

    protected static function getPmCompliance(string $period): array
    {
        $compliance = PmCompliance::query();
        
        switch ($period) {
            case 'month':
                $compliance->where('created_at', '>=', now()->subMonth());
                break;
            case 'quarter':
                $compliance->where('created_at', '>=', now()->subMonths(3));
                break;
            case 'year':
                $compliance->where('created_at', '>=', now()->subYear());
                break;
        }
        
        $records = $compliance->get();
        
        return [
            'period' => $period,
            'total_pm' => $records->sum('total_pm'),
            'completed_pm' => $records->sum('completed_pm'),
            'overdue_pm' => $records->sum('overdue_pm'),
            'average_compliance_rate' => $records->avg('compliance_percentage') ?? 0,
        ];
    }

    protected static function getWoStatistics(string $period): array
    {
        $query = WorkOrder::query();
        
        switch ($period) {
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'quarter':
                $query->where('created_at', '>=', now()->subMonths(3));
                break;
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
        }
        
        $workOrders = $query->get();
        
        return [
            'period' => $period,
            'total' => $workOrders->count(),
            'by_status' => [
                'open' => $workOrders->where('status', 'open')->count(),
                'in_progress' => $workOrders->where('status', 'in_progress')->count(),
                'completed' => $workOrders->where('status', 'completed')->count(),
                'closed' => $workOrders->where('status', 'closed')->count(),
            ],
            'by_type' => [
                'corrective' => $workOrders->where('type', 'corrective')->count(),
                'preventive' => $workOrders->where('type', 'preventive')->count(),
                'inspection' => $workOrders->where('type', 'inspection')->count(),
            ],
            'by_priority' => [
                'critical' => $workOrders->where('priority', 'critical')->count(),
                'high' => $workOrders->where('priority', 'high')->count(),
                'medium' => $workOrders->where('priority', 'medium')->count(),
                'low' => $workOrders->where('priority', 'low')->count(),
            ],
        ];
    }

    protected static function getMaintenanceCosts(?string $equipmentName, string $period): array
    {
        $startDate = match($period) {
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
        
        $pmCosts = PmCost::where('created_at', '>=', $startDate);
        $woCosts = WoCost::where('created_at', '>=', $startDate);
        
        if ($equipmentName) {
            // Filter by equipment if needed
        }
        
        return [
            'period' => $period,
            'total_cost' => $pmCosts->sum('total_cost') + $woCosts->sum('total_cost'),
            'pm_cost' => $pmCosts->sum('total_cost'),
            'wo_cost' => $woCosts->sum('total_cost'),
            'labour_cost' => $pmCosts->sum('labour_cost') + $woCosts->sum('labour_cost'),
            'parts_cost' => $pmCosts->sum('parts_cost') + $woCosts->sum('parts_cost'),
        ];
    }

    protected static function getTechnicianWorkload(?string $technicianName): array
    {
        $query = User::where('role', 'technician')->where('is_active', 1);
        
        if ($technicianName) {
            $query->where('name', 'like', "%{$technicianName}%");
        }
        
        $technicians = $query->get();
        
        $result = $technicians->map(function($tech) {
            // Count active WO via wo_processes table
            $activeWo = DB::table('wo_processes as wp')
                ->join('work_orders as wo', 'wp.work_order_id', '=', 'wo.id')
                ->where('wp.performed_by_gpid', $tech->gpid)
                ->whereIn('wo.status', ['open', 'in_progress'])
                ->count();
                
            return [
                'name' => $tech->name,
                'department' => $tech->department,
                'active_work_orders' => $activeWo,
                'status' => $activeWo > 5 ? 'Busy' : 'Available',
            ];
        });
        
        return [
            'total_technicians' => $technicians->count(),
            'technicians' => $result->toArray(),
        ];
    }

    protected static function getEquipmentDowntime(string $equipmentName, int $days): array
    {
        // Placeholder implementation
        return [
            'equipment' => $equipmentName,
            'period_days' => $days,
            'total_downtime_minutes' => 0,
            'message' => 'Downtime tracking feature coming soon',
        ];
    }

    protected static function getTopIssues(int $limit, string $period): array
    {
        $startDate = match($period) {
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
        
        $issues = DB::table('equipment_troubles')
            ->select('issue_description', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('issue_description')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
        
        return [
            'period' => $period,
            'top_issues' => $issues->map(fn($issue) => [
                'issue' => $issue->issue_description,
                'occurrences' => $issue->count,
            ])->toArray(),
        ];
    }

    protected static function getEquipmentReliability(string $equipmentName): array
    {
        return [
            'equipment' => $equipmentName,
            'mtbf' => 'Calculating...',
            'mttr' => 'Calculating...',
            'availability' => 'Calculating...',
            'message' => 'Reliability metrics calculation in progress',
        ];
    }

    protected static function queryDatabase(string $queryDescription): array
    {
        return [
            'query_description' => $queryDescription,
            'message' => 'Untuk query kompleks, gunakan function spesifik yang tersedia atau hubungi administrator',
        ];
    }
}
