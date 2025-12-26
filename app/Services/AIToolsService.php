<?php

namespace App\Services;

use Exception;
use App\Models\Compressor1Checklist;
use App\Models\Compressor2Checklist;
use App\Models\Chiller1Checklist;
use App\Models\Chiller2Checklist;
use App\Models\AhuChecklist;
use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\PmExecution;
use App\Models\RunningHour;
use App\Models\EquipmentTrouble;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIToolsService
{
    /**
     * Get available tools/functions for AI
     */
    public static function getToolDefinitions(): array
    {
        // Merge basic and extended tools
        $basicTools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_info',
                    'description' => 'Mendapatkan informasi detail equipment berdasarkan nama atau kode. Berguna untuk melihat spesifikasi, status, lokasi equipment.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'search' => [
                                'type' => 'string',
                                'description' => 'Nama atau kode equipment (contoh: "Chiller 1", "COMP-001")',
                            ],
                        ],
                        'required' => ['search'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_history',
                    'description' => 'Mendapatkan riwayat maintenance dan perbaikan equipment dalam 30 hari terakhir',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => [
                                'type' => 'string',
                                'description' => 'Nama equipment',
                            ],
                            'days' => [
                                'type' => 'integer',
                                'description' => 'Jumlah hari ke belakang (default: 30)',
                            ],
                        ],
                        'required' => ['equipment_name'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_active_work_orders',
                    'description' => 'Mendapatkan daftar work order yang sedang aktif/open',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'priority' => [
                                'type' => 'string',
                                'description' => 'Filter berdasarkan priority: high, medium, low',
                                'enum' => ['high', 'medium', 'low'],
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_equipment_troubles',
                    'description' => 'Mendapatkan daftar trouble/masalah equipment yang pernah terjadi',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => [
                                'type' => 'string',
                                'description' => 'Nama equipment (opsional)',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Jumlah data (default: 10)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_running_hours',
                    'description' => 'Mendapatkan data running hours/waktu operasi equipment terakhir',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_name' => [
                                'type' => 'string',
                                'description' => 'Nama equipment',
                            ],
                        ],
                        'required' => ['equipment_name'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_checklist_data',
                    'description' => 'Mendapatkan data checklist untuk equipment (Compressor 1, Compressor 2, Chiller 1, Chiller 2, AHU)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_type' => [
                                'type' => 'string',
                                'description' => 'Jenis equipment: compressor1, compressor2, chiller1, chiller2, ahu',
                                'enum' => ['compressor1', 'compressor2', 'chiller1', 'chiller2', 'ahu'],
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Jumlah data terakhir (default: 10)',
                            ],
                            'shift' => [
                                'type' => 'integer',
                                'description' => 'Filter by shift: 1, 2, atau 3',
                            ],
                        ],
                        'required' => ['equipment_type'],
                    ],
                ],
            ],
        ];
        
        // Get extended tools from AIToolsExtended
        $extendedTools = AIToolsExtended::getExtendedToolDefinitions();
        
        // Merge and return all tools
        return array_merge($basicTools, $extendedTools);
    }

    /**
     * Execute a tool/function call
     */
    public static function executeTool(string $functionName, array $arguments): array
    {
        // Check if it's an extended tool
        $extendedTools = [
            'get_areas_list', 'search_parts', 'get_inventory_stock', 'get_stock_alerts',
            'get_pm_schedules', 'get_pm_compliance', 'get_wo_statistics', 
            'get_maintenance_costs', 'get_technician_workload', 'get_equipment_downtime',
            'get_top_issues', 'get_equipment_reliability', 'query_database', 'generate_excel_report',
            'analyze_root_cause', 'analyze_cost_optimization', 'detect_anomalies',
            'predict_maintenance_needs', 'benchmark_performance', 'generate_maintenance_briefing',
            'get_proactive_recommendations', 'simulate_scenario', 'send_whatsapp_briefing',
            'analyze_parameter_trends', 'smart_query', 'get_plant_summary'
        ];
        
        if (in_array($functionName, $extendedTools)) {
            return AIToolsExtended::executeExtendedTool($functionName, $arguments);
        }
        
        // Execute basic tools
        try {
            return match ($functionName) {
                'get_equipment_info' => self::getEquipmentInfo($arguments['search'] ?? ''),
                'get_equipment_history' => self::getEquipmentHistory(
                    $arguments['equipment_name'] ?? '',
                    $arguments['days'] ?? 30
                ),
                'get_active_work_orders' => self::getActiveWorkOrders($arguments['priority'] ?? null),
                'get_equipment_troubles' => self::getEquipmentTroubles(
                    $arguments['equipment_name'] ?? null,
                    $arguments['limit'] ?? 10
                ),
                'get_running_hours' => self::getRunningHours($arguments['equipment_name'] ?? ''),
                'get_checklist_data' => self::getChecklistData(
                    $arguments['equipment_type'] ?? '',
                    $arguments['limit'] ?? 10,
                    $arguments['shift'] ?? null
                ),
                default => ['error' => 'Function not found'],
            };
        } catch (Exception $e) {
            Log::error('AI Tool execution failed', [
                'function' => $functionName,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Security: Return generic error message instead of exception details
            return ['error' => 'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.'];
        }
    }

    /**
     * Get equipment information
     */
    protected static function getEquipmentInfo(string $search): array
    {
        $equipment = Asset::with(['subArea.area'])
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->where('is_active', 1)
            ->first();

        if (!$equipment) {
            return ['error' => "Equipment '{$search}' tidak ditemukan"];
        }

        return [
            'name' => $equipment->name,
            'code' => $equipment->code,
            'model' => $equipment->model,
            'serial_number' => $equipment->serial_number,
            'installation_date' => $equipment->installation_date?->format('d-m-Y'),
            'location' => [
                'area' => $equipment->subArea?->area?->name,
                'sub_area' => $equipment->subArea?->name,
            ],
            'status' => $equipment->is_active ? 'Active' : 'Inactive',
        ];
    }

    /**
     * Get equipment maintenance history
     */
    protected static function getEquipmentHistory(string $equipmentName, int $days): array
    {
        $equipment = Asset::where('name', 'like', "%{$equipmentName}%")->first();

        if (!$equipment) {
            return ['error' => "Equipment '{$equipmentName}' tidak ditemukan"];
        }

        // Get PM executions
        $pmExecutions = PmExecution::whereHas('pmSchedule', function ($query) use ($equipment) {
            $query->whereHas('subAsset', function ($q) use ($equipment) {
                $q->where('asset_id', $equipment->id);
            });
        })
            ->where('execution_date', '>=', now()->subDays($days))
            ->orderBy('execution_date', 'desc')
            ->limit(10)
            ->get(['execution_date', 'status', 'findings', 'recommendations'])
            ->map(fn ($pm) => [
                'date' => $pm->execution_date->format('d-m-Y'),
                'type' => 'Preventive Maintenance',
                'status' => $pm->status,
                'findings' => $pm->findings,
                'recommendations' => $pm->recommendations,
            ]);

        // Get work orders
        $workOrders = WorkOrder::whereHas('subAsset', function ($query) use ($equipment) {
            $query->where('asset_id', $equipment->id);
        })
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['wo_number', 'type', 'description', 'status', 'priority', 'created_at'])
            ->map(fn ($wo) => [
                'date' => $wo->created_at->format('d-m-Y'),
                'wo_number' => $wo->wo_number,
                'type' => $wo->type,
                'description' => $wo->description,
                'status' => $wo->status,
                'priority' => $wo->priority,
            ]);

        return [
            'equipment' => $equipment->name,
            'period' => "{$days} hari terakhir",
            'preventive_maintenance' => $pmExecutions->toArray(),
            'work_orders' => $workOrders->toArray(),
            'summary' => [
                'total_pm' => $pmExecutions->count(),
                'total_wo' => $workOrders->count(),
            ],
        ];
    }

    /**
     * Get active work orders
     */
    protected static function getActiveWorkOrders(?string $priority): array
    {
        $query = WorkOrder::with(['subAsset.asset'])
            ->whereIn('status', ['open', 'in_progress'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');

        if ($priority) {
            $query->where('priority', $priority);
        }

        $workOrders = $query->limit(20)->get();

        return [
            'total' => $workOrders->count(),
            'work_orders' => $workOrders->map(fn ($wo) => [
                'wo_number' => $wo->wo_number,
                'equipment' => $wo->subAsset?->asset?->name,
                'component' => $wo->subAsset?->name,
                'type' => $wo->type,
                'description' => $wo->description,
                'priority' => $wo->priority,
                'status' => $wo->status,
                'created' => $wo->created_at->format('d-m-Y H:i'),
            ])->toArray(),
        ];
    }

    /**
     * Get equipment troubles
     */
    protected static function getEquipmentTroubles(?string $equipmentName, int $limit): array
    {
        $query = EquipmentTrouble::with(['equipment.asset'])
            ->orderBy('created_at', 'desc');

        if ($equipmentName) {
            $query->whereHas('equipment.asset', function ($q) use ($equipmentName) {
                $q->where('name', 'like', "%{$equipmentName}%");
            });
        }

        $troubles = $query->limit($limit)->get();

        return [
            'total' => $troubles->count(),
            'troubles' => $troubles->map(fn ($trouble) => [
                'title' => $trouble->title,
                'equipment' => $trouble->equipment?->asset?->name,
                'component' => $trouble->equipment?->name,
                'issue_description' => $trouble->issue_description,
                'priority' => $trouble->priority,
                'status' => $trouble->status,
                'resolution_notes' => $trouble->resolution_notes,
                'downtime_minutes' => $trouble->downtime_minutes,
                'date' => $trouble->reported_at?->format('d-m-Y H:i') ?? $trouble->created_at->format('d-m-Y H:i'),
            ])->toArray(),
        ];
    }

    /**
     * Get running hours
     */
    protected static function getRunningHours(string $equipmentName): array
    {
        $equipment = Asset::where('name', 'like', "%{$equipmentName}%")->first();

        if (!$equipment) {
            return ['error' => "Equipment '{$equipmentName}' tidak ditemukan"];
        }

        $runningHours = RunningHour::whereHas('subAsset', function ($query) use ($equipment) {
            $query->where('asset_id', $equipment->id);
        })
            ->orderBy('recorded_date', 'desc')
            ->limit(30)
            ->get(['recorded_date', 'hours', 'notes'])
            ->map(fn ($rh) => [
                'date' => $rh->recorded_date->format('d-m-Y'),
                'hours' => $rh->hours,
                'notes' => $rh->notes,
            ]);

        $latest = $runningHours->first();
        $totalHours = $runningHours->sum('hours');

        return [
            'equipment' => $equipment->name,
            'latest_reading' => $latest,
            'total_hours_30_days' => $totalHours,
            'average_per_day' => $runningHours->count() > 0 ? round($totalHours / $runningHours->count(), 2) : 0,
            'history' => $runningHours->take(10)->toArray(),
        ];
    }

    /**
     * Get checklist data
     */
    protected static function getChecklistData(string $equipmentType, int $limit, ?int $shift): array
    {
        $modelMap = [
            'compressor1' => Compressor1Checklist::class,
            'compressor2' => Compressor2Checklist::class,
            'chiller1' => Chiller1Checklist::class,
            'chiller2' => Chiller2Checklist::class,
            'ahu' => AhuChecklist::class,
        ];

        if (!isset($modelMap[$equipmentType])) {
            return ['error' => "Equipment type '{$equipmentType}' tidak valid. Pilihan: compressor1, compressor2, chiller1, chiller2, ahu"];
        }

        $model = $modelMap[$equipmentType];
        $query = $model::with('user')->orderBy('created_at', 'desc');

        if ($shift !== null) {
            $query->where('shift', $shift);
        }

        $checklists = $query->limit($limit)->get();

        // Get summary statistics
        $latestRecord = $checklists->first();
        
        return [
            'equipment_type' => ucfirst($equipmentType),
            'total_records' => $checklists->count(),
            'shift_filter' => $shift,
            'latest_record' => $latestRecord ? [
                'shift' => $latestRecord->shift,
                'operator' => $latestRecord->name,
                'date' => $latestRecord->created_at->format('d-m-Y H:i'),
                'data' => self::formatChecklistRecord($equipmentType, $latestRecord),
            ] : null,
            'recent_records' => $checklists->take(5)->map(fn($record) => [
                'shift' => $record->shift,
                'operator' => $record->name,
                'date' => $record->created_at->format('d-m-Y H:i'),
                'summary' => self::getChecklistSummary($equipmentType, $record),
            ])->toArray(),
        ];
    }

    /**
     * Format checklist record based on equipment type
     */
    protected static function formatChecklistRecord(string $equipmentType, $record): array
    {
        switch ($equipmentType) {
            case 'compressor1':
            case 'compressor2':
                return [
                    'tot_run_hours' => $record->tot_run_hours,
                    'bearing_oil_temperature' => $record->bearing_oil_temperature,
                    'bearing_oil_pressure' => $record->bearing_oil_pressure,
                    'discharge_pressure' => $record->discharge_pressure,
                    'discharge_temperature' => $record->discharge_temperature,
                    'cws_temperature' => $record->cws_temperature,
                    'cwr_temperature' => $record->cwr_temperature,
                    'refrigerant_pressure' => $record->refrigerant_pressure,
                    'dew_point' => $record->dew_point,
                    'notes' => $record->notes,
                ];
            
            case 'chiller1':
            case 'chiller2':
                return [
                    'run_hours' => $record->run_hours,
                    'sat_evap_t' => $record->sat_evap_t,
                    'sat_dis_t' => $record->sat_dis_t,
                    'evap_p' => $record->evap_p,
                    'conds_p' => $record->conds_p,
                    'motor_amps' => $record->motor_amps,
                    'motor_volts' => $record->motor_volts,
                    'motor_t' => $record->motor_t,
                    'comp_oil_level' => $record->comp_oil_level,
                    'notes' => $record->notes,
                ];
            
            case 'ahu':
                return [
                    'run_hours' => $record->run_hours ?? null,
                    'temperature' => $record->temperature ?? null,
                    'pressure' => $record->pressure ?? null,
                    'notes' => $record->notes ?? null,
                ];
            
            default:
                return [];
        }
    }

    /**
     * Get summary of checklist record
     */
    protected static function getChecklistSummary(string $equipmentType, $record): string
    {
        switch ($equipmentType) {
            case 'compressor1':
            case 'compressor2':
                return "Run Hours: {$record->tot_run_hours}, Discharge Temp: {$record->discharge_temperature}°C, Bearing Oil Temp: {$record->bearing_oil_temperature}°C";
            
            case 'chiller1':
            case 'chiller2':
                return "Run Hours: {$record->run_hours}, Motor Amps: {$record->motor_amps}A, Evap Pressure: {$record->evap_p}";
            
            case 'ahu':
                return "Status normal";
            
            default:
                return 'No summary available';
        }
    }
}
