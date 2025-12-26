<?php

namespace App\Services;

use stdClass;
use Exception;
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
                        'properties' => new stdClass(),
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
                        'properties' => new stdClass(),
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
                    'description' => 'Generate dan download file Excel untuk rekapan data apapun dari database. Mendukung: Work Orders, PM (Schedules/Executions/Compliance), Assets, Sub Assets, Running Hours, Inventory, Parts, Stock Alerts, CBM, Equipment Troubles, Abnormalities, Kaizen, RCA, Utility Consumption, Production Records, Areas, Sub Areas, Users, dan semua Checklist (Compressor/Chiller/AHU). Gunakan untuk permintaan export/download/rekapan data.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'report_type' => [
                                'type' => 'string',
                                'description' => 'Tipe report yang akan digenerate',
                                'enum' => [
                                    'work_orders', 'pm_executions', 'pm_schedules', 'pm_compliance',
                                    'assets', 'sub_assets', 'running_hours',
                                    'inventory', 'inventory_movements', 'parts', 'stock_alerts',
                                    'cbm_schedules', 'cbm_executions',
                                    'equipment_troubles', 'abnormalities',
                                    'kaizen', 'root_cause_analysis',
                                    'utility_consumption', 'production_records',
                                    'areas', 'sub_areas', 'users',
                                    'compressor1_checklist', 'compressor2_checklist', 
                                    'chiller1_checklist', 'chiller2_checklist', 'ahu_checklist'
                                ],
                            ],
                            'period' => [
                                'type' => 'string',
                                'description' => 'Periode data',
                                'enum' => ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_quarter', 'this_year', 'last_year'],
                            ],
                            'status' => ['type' => 'string', 'description' => 'Filter status (optional)'],
                            'priority' => ['type' => 'string', 'description' => 'Filter priority (optional)'],
                            'shift' => ['type' => 'integer', 'description' => 'Filter shift untuk checklist (optional)'],
                            'role' => ['type' => 'string', 'description' => 'Filter role untuk users report (optional)'],
                            'department' => ['type' => 'string', 'description' => 'Filter department (optional)'],
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
            
            // ========================================================================
            // PHASE 2: PREDICTIVE MAINTENANCE & PERFORMANCE MONITORING
            // ========================================================================
            [
                'type' => 'function',
                'function' => [
                    'name' => 'predict_maintenance_needs',
                    'description' => 'Prediksi kebutuhan maintenance berdasarkan pola historis, MTBF, running hours, dan PM compliance. Identifikasi equipment yang berisiko tinggi untuk breakdown. Gunakan untuk pertanyaan "prediksi maintenance", "equipment mana yang perlu perhatian", "forecast kerusakan", "risk assessment".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_id' => [
                                'type' => 'integer',
                                'description' => 'ID equipment spesifik untuk prediksi. Kosongkan untuk analisa semua equipment.',
                            ],
                            'prediction_days' => [
                                'type' => 'integer',
                                'description' => 'Periode prediksi dalam hari (default: 30 hari)',
                                'enum' => [7, 14, 30, 60, 90],
                            ],
                            'include_all_equipment' => [
                                'type' => 'boolean',
                                'description' => 'Include semua equipment dalam analisa (default: true jika equipment_id kosong)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'benchmark_performance',
                    'description' => 'Benchmark performa equipment dengan metrics: uptime, MTBF, MTTR, PM compliance, cost efficiency. Bandingkan antar equipment atau dengan periode sebelumnya. Gunakan untuk pertanyaan "perbandingan performa", "equipment mana yang bagus/jelek", "benchmark", "KPI equipment".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_id' => [
                                'type' => 'integer',
                                'description' => 'ID equipment spesifik untuk benchmark. Kosongkan untuk analisa semua equipment.',
                            ],
                            'period' => [
                                'type' => 'integer',
                                'description' => 'Periode analisa dalam hari (default: 90 hari)',
                                'enum' => [30, 60, 90, 180, 365],
                            ],
                            'compare_with' => [
                                'type' => 'string',
                                'description' => 'Bandingkan dengan: peers (equipment sejenis), historical (periode sebelumnya), target (target KPI)',
                                'enum' => ['peers', 'historical', 'target'],
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_maintenance_briefing',
                    'description' => 'Generate laporan ringkasan maintenance komprehensif. Include: critical alerts, WO summary, PM status, equipment health, key metrics, action plan, dan recommendations. Gunakan untuk pertanyaan "briefing hari ini", "laporan maintenance", "summary harian/mingguan/bulanan", "apa yang perlu dikerjakan".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'description' => 'Tipe briefing: daily (harian), weekly (mingguan), monthly (bulanan). Default: daily',
                                'enum' => ['daily', 'weekly', 'monthly'],
                            ],
                            'include_details' => [
                                'type' => 'boolean',
                                'description' => 'Include detail lengkap atau ringkasan saja (default: true)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            // Phase 3: Smart Recommendations, What-If Simulator, WhatsApp Integration
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_proactive_recommendations',
                    'description' => 'Dapatkan rekomendasi proaktif berdasarkan kondisi sistem CMMS. Analisis: PM yang terlambat, equipment bermasalah, stok rendah, biaya tinggi, masalah keamanan. Gunakan untuk pertanyaan "rekomendasi", "apa yang perlu diperbaiki", "prioritas", "saran improvement", "action items".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Filter berdasarkan kategori rekomendasi',
                                'enum' => ['all', 'maintenance', 'inventory', 'cost', 'safety'],
                            ],
                            'urgency_level' => [
                                'type' => 'string',
                                'description' => 'Filter berdasarkan tingkat urgensi minimum',
                                'enum' => ['all', 'low', 'medium', 'high', 'critical'],
                            ],
                            'max_recommendations' => [
                                'type' => 'integer',
                                'description' => 'Jumlah maksimal rekomendasi yang ditampilkan (default: 20)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'simulate_scenario',
                    'description' => 'Simulasi what-if untuk berbagai skenario maintenance. Skenario: ubah frekuensi PM, tambah equipment, perubahan budget, perubahan staffing, shutdown impact. Gunakan untuk pertanyaan "bagaimana jika", "simulasi", "impact analysis", "what if", "prediksi dampak".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'scenario_type' => [
                                'type' => 'string',
                                'description' => 'Tipe skenario yang akan disimulasikan',
                                'enum' => ['pm_frequency', 'add_equipment', 'budget_change', 'staffing_change', 'shutdown_impact'],
                            ],
                            'equipment_id' => [
                                'type' => 'integer',
                                'description' => 'ID equipment untuk skenario yang memerlukan equipment spesifik',
                            ],
                            'parameters' => [
                                'type' => 'object',
                                'description' => 'Parameter spesifik untuk skenario: pm_frequency (new_frequency_days), budget_change (change_percent), staffing_change (new_technician_count), shutdown_impact (shutdown_duration_days)',
                            ],
                            'simulation_period' => [
                                'type' => 'integer',
                                'description' => 'Periode simulasi dalam hari (default: 365)',
                            ],
                        ],
                        'required' => ['scenario_type'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'send_whatsapp_briefing',
                    'description' => 'Kirim briefing maintenance melalui WhatsApp ke group yang sudah dikonfigurasi. Include: urgent alerts, KPI summary, action items. Gunakan saat user minta "kirim ke whatsapp", "share ke whatsapp", "broadcast briefing".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'description' => 'Tipe briefing yang dikirim',
                                'enum' => ['daily', 'weekly', 'alert', 'custom'],
                            ],
                            'recipient_group' => [
                                'type' => 'string',
                                'description' => 'Target group WhatsApp (gunakan default jika tidak ditentukan)',
                            ],
                            'custom_message' => [
                                'type' => 'string',
                                'description' => 'Pesan custom untuk ditambahkan (opsional)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            
            // ========================================================================
            // PHASE 4: TREND ANALYSIS, NATURAL LANGUAGE QUERY, SMART SUMMARY
            // ========================================================================
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_parameter_trends',
                    'description' => 'Analisa tren parameter checklist equipment dari waktu ke waktu. Identifikasi apakah parameter (temperature, pressure, running hours, dll) naik, turun, stabil, atau anomali. Gunakan untuk pertanyaan "tren temperature", "bagaimana perkembangan", "parameter naik/turun", "analisa historis".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'equipment_type' => [
                                'type' => 'string',
                                'description' => 'Tipe equipment: compressor1, compressor2, chiller1, chiller2, ahu',
                                'enum' => ['compressor1', 'compressor2', 'chiller1', 'chiller2', 'ahu'],
                            ],
                            'parameter_name' => [
                                'type' => 'string',
                                'description' => 'Nama parameter yang akan dianalisa (contoh: discharge_temperature, motor_amps, sat_evap_t). Kosongkan untuk analisa semua parameter penting.',
                            ],
                            'period_days' => [
                                'type' => 'integer',
                                'description' => 'Periode analisa dalam hari (default: 30)',
                                'enum' => [7, 14, 30, 60, 90],
                            ],
                            'shift' => [
                                'type' => 'integer',
                                'description' => 'Filter by shift: 1, 2, atau 3. Kosongkan untuk semua shift.',
                                'enum' => [1, 2, 3],
                            ],
                        ],
                        'required' => ['equipment_type'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'smart_query',
                    'description' => 'Jawab pertanyaan apapun tentang data CMMS dengan bahasa natural. AI akan otomatis query database yang relevan dan memberikan jawaban. Gunakan untuk pertanyaan umum seperti "berapa total WO bulan ini", "equipment mana yang paling sering rusak", "siapa teknisi yang paling produktif", "rata-rata downtime minggu ini".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => [
                                'type' => 'string',
                                'description' => 'Pertanyaan dalam bahasa natural tentang data CMMS',
                            ],
                        ],
                        'required' => ['question'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_plant_summary',
                    'description' => 'Dapatkan ringkasan lengkap kondisi plant/pabrik saat ini. Include: equipment health, active issues, PM status, inventory alerts, KPI overview, dan action priorities. Gunakan untuk pertanyaan "kondisi plant", "status keseluruhan", "overview hari ini", "ringkasan plant", "bagaimana kondisi pabrik".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'detail_level' => [
                                'type' => 'string',
                                'description' => 'Level detail: brief (ringkas), standard (normal), detailed (lengkap)',
                                'enum' => ['brief', 'standard', 'detailed'],
                            ],
                            'focus_area' => [
                                'type' => 'string',
                                'description' => 'Fokus area tertentu (opsional): equipment, maintenance, inventory, production',
                                'enum' => ['all', 'equipment', 'maintenance', 'inventory', 'production'],
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
                // Phase 1: Analytics Functions
                'analyze_root_cause' => (new AIAnalyticsService())->analyzeRootCause($arguments),
                'analyze_cost_optimization' => (new AIAnalyticsService())->analyzeCostOptimization($arguments),
                'detect_anomalies' => (new AIAnalyticsService())->detectAnomalies($arguments),
                // Phase 2: Predictive & Benchmarking Functions
                'predict_maintenance_needs' => (new AIAnalyticsService())->predictMaintenanceNeeds($arguments),
                'benchmark_performance' => (new AIAnalyticsService())->benchmarkPerformance($arguments),
                'generate_maintenance_briefing' => (new AIAnalyticsService())->generateMaintenanceBriefing($arguments),
                // Phase 3: Smart Recommendations, What-If Simulator, WhatsApp Integration
                'get_proactive_recommendations' => (new AIAnalyticsService())->getProactiveRecommendations($arguments),
                'simulate_scenario' => (new AIAnalyticsService())->simulateScenario($arguments),
                'send_whatsapp_briefing' => (new AIAnalyticsService())->sendWhatsAppBriefing($arguments),
                // Phase 4: Trend Analysis, Natural Language Query, Smart Summary
                'analyze_parameter_trends' => self::analyzeParameterTrends($arguments),
                'smart_query' => self::smartQuery($arguments),
                'get_plant_summary' => self::getPlantSummary($arguments),
                default => ['error' => 'Function not found'],
            };
        } catch (Exception $e) {
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
    
    // ========================================================================
    // PHASE 4: TREND ANALYSIS, NATURAL LANGUAGE QUERY, SMART SUMMARY
    // ========================================================================
    
    /**
     * Analyze parameter trends for checklist data
     */
    protected static function analyzeParameterTrends(array $arguments): array
    {
        $equipmentType = $arguments['equipment_type'] ?? 'compressor1';
        $parameterName = $arguments['parameter_name'] ?? null;
        $periodDays = $arguments['period_days'] ?? 30;
        $shift = $arguments['shift'] ?? null;
        
        // Get model class
        $modelClass = match($equipmentType) {
            'compressor1' => \App\Models\Compressor1Checklist::class,
            'compressor2' => \App\Models\Compressor2Checklist::class,
            'chiller1' => \App\Models\Chiller1Checklist::class,
            'chiller2' => \App\Models\Chiller2Checklist::class,
            'ahu' => \App\Models\AhuChecklist::class,
            default => \App\Models\Compressor1Checklist::class,
        };
        
        // Define important parameters per equipment type
        $importantParams = match($equipmentType) {
            'compressor1', 'compressor2' => [
                'discharge_temperature' => ['min' => 60, 'max' => 95, 'unit' => '°C'],
                'discharge_pressure' => ['min' => 6, 'max' => 10, 'unit' => 'bar'],
                'bearing_oil_temperature' => ['min' => 40, 'max' => 70, 'unit' => '°C'],
                'bearing_oil_pressure' => ['min' => 2, 'max' => 5, 'unit' => 'bar'],
                'cws_temperature' => ['min' => 7, 'max' => 12, 'unit' => '°C'],
                'cwr_temperature' => ['min' => 12, 'max' => 18, 'unit' => '°C'],
                'tot_run_hours' => ['min' => 0, 'max' => 99999, 'unit' => 'hours'],
            ],
            'chiller1', 'chiller2' => [
                'sat_evap_t' => ['min' => 2, 'max' => 8, 'unit' => '°C'],
                'sat_dis_t' => ['min' => 35, 'max' => 45, 'unit' => '°C'],
                'evap_p' => ['min' => 3, 'max' => 6, 'unit' => 'bar'],
                'conds_p' => ['min' => 10, 'max' => 15, 'unit' => 'bar'],
                'motor_amps' => ['min' => 100, 'max' => 400, 'unit' => 'A'],
                'motor_volts' => ['min' => 380, 'max' => 420, 'unit' => 'V'],
                'run_hours' => ['min' => 0, 'max' => 99999, 'unit' => 'hours'],
            ],
            'ahu' => [
                'ahu_mb_1_1_hf' => ['min' => 0, 'max' => 100, 'unit' => '%'],
                'ahu_mb_1_1_pf' => ['min' => 0, 'max' => 100, 'unit' => '%'],
                'ahu_mb_1_1_mf' => ['min' => 0, 'max' => 100, 'unit' => '%'],
            ],
            default => [],
        };
        
        // If specific parameter requested, filter
        if ($parameterName && isset($importantParams[$parameterName])) {
            $importantParams = [$parameterName => $importantParams[$parameterName]];
        }
        
        // Query data
        $query = $modelClass::where('created_at', '>=', now()->subDays($periodDays));
        if ($shift) {
            $query->where('shift', $shift);
        }
        $data = $query->orderBy('created_at', 'asc')->get();
        
        if ($data->isEmpty()) {
            return [
                'equipment_type' => $equipmentType,
                'period_days' => $periodDays,
                'message' => 'Tidak ada data checklist untuk periode ini',
                'trends' => [],
            ];
        }
        
        // Analyze trends for each parameter
        $trends = [];
        foreach ($importantParams as $param => $config) {
            $values = $data->pluck($param)->filter(fn($v) => $v !== null)->values();
            
            if ($values->isEmpty()) continue;
            
            $firstHalf = $values->take(intval($values->count() / 2));
            $secondHalf = $values->skip(intval($values->count() / 2));
            
            $avgFirst = $firstHalf->avg();
            $avgSecond = $secondHalf->avg();
            $overallAvg = $values->avg();
            $min = $values->min();
            $max = $values->max();
            $latest = $values->last();
            
            // Calculate trend direction
            $changePercent = $avgFirst > 0 ? (($avgSecond - $avgFirst) / $avgFirst) * 100 : 0;
            
            $trendDirection = match(true) {
                $changePercent > 10 => 'increasing ⬆️',
                $changePercent < -10 => 'decreasing ⬇️',
                default => 'stable ➡️',
            };
            
            // Check if out of normal range
            $status = 'normal';
            $alert = null;
            if ($latest < $config['min']) {
                $status = 'below_normal';
                $alert = "⚠️ Nilai saat ini ({$latest}) di bawah batas minimum ({$config['min']})";
            } elseif ($latest > $config['max']) {
                $status = 'above_normal';
                $alert = "⚠️ Nilai saat ini ({$latest}) di atas batas maximum ({$config['max']})";
            }
            
            $trends[$param] = [
                'parameter' => ucwords(str_replace('_', ' ', $param)),
                'unit' => $config['unit'],
                'current_value' => round($latest, 2),
                'average' => round($overallAvg, 2),
                'min' => round($min, 2),
                'max' => round($max, 2),
                'normal_range' => "{$config['min']} - {$config['max']} {$config['unit']}",
                'trend' => $trendDirection,
                'change_percent' => round($changePercent, 1) . '%',
                'status' => $status,
                'alert' => $alert,
                'data_points' => $values->count(),
            ];
        }
        
        // Generate summary
        $alertCount = collect($trends)->filter(fn($t) => $t['alert'] !== null)->count();
        $increasingCount = collect($trends)->filter(fn($t) => str_contains($t['trend'], 'increasing'))->count();
        $decreasingCount = collect($trends)->filter(fn($t) => str_contains($t['trend'], 'decreasing'))->count();
        
        return [
            'equipment_type' => strtoupper(str_replace('_', ' ', $equipmentType)),
            'period_days' => $periodDays,
            'shift' => $shift ?? 'Semua shift',
            'total_records' => $data->count(),
            'analysis_date' => now()->format('d-m-Y H:i'),
            'summary' => [
                'total_parameters_analyzed' => count($trends),
                'parameters_with_alerts' => $alertCount,
                'increasing_trends' => $increasingCount,
                'decreasing_trends' => $decreasingCount,
                'stable_trends' => count($trends) - $increasingCount - $decreasingCount,
            ],
            'trends' => $trends,
            'recommendation' => $alertCount > 0 
                ? "⚠️ Ada {$alertCount} parameter yang perlu perhatian. Lakukan pengecekan lebih lanjut."
                : "✅ Semua parameter dalam kondisi normal.",
        ];
    }
    
    /**
     * Smart natural language query
     */
    protected static function smartQuery(array $arguments): array
    {
        $question = strtolower($arguments['question'] ?? '');
        
        // Pattern matching for common questions
        $result = null;
        
        // Work Order queries
        if (preg_match('/(total|jumlah|berapa).*(wo|work order)/i', $question)) {
            $period = self::extractPeriod($question);
            $query = WorkOrder::query();
            $query = self::applyPeriodToQuery($query, $period);
            
            $total = $query->count();
            $byStatus = [
                'open' => (clone $query)->where('status', 'open')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'closed' => (clone $query)->where('status', 'closed')->count(),
            ];
            
            $result = [
                'question' => $arguments['question'],
                'answer' => "Total Work Order {$period['label']}: {$total}",
                'details' => [
                    'total' => $total,
                    'by_status' => $byStatus,
                    'period' => $period['label'],
                ],
            ];
        }
        
        // Equipment trouble queries
        elseif (preg_match('/(equipment|mesin).*(sering|paling).*(rusak|trouble|masalah)/i', $question)) {
            $period = self::extractPeriod($question);
            
            $troubles = DB::table('equipment_troubles as et')
                ->join('sub_assets as sa', 'et.sub_asset_id', '=', 'sa.id')
                ->join('assets as a', 'sa.asset_id', '=', 'a.id')
                ->select('a.name as equipment', DB::raw('COUNT(*) as trouble_count'))
                ->where('et.created_at', '>=', $period['start'])
                ->groupBy('a.id', 'a.name')
                ->orderBy('trouble_count', 'desc')
                ->limit(5)
                ->get();
            
            $topEquipment = $troubles->first();
            $result = [
                'question' => $arguments['question'],
                'answer' => $topEquipment 
                    ? "Equipment paling sering bermasalah {$period['label']}: {$topEquipment->equipment} ({$topEquipment->trouble_count}x)"
                    : "Tidak ada data trouble {$period['label']}",
                'details' => [
                    'ranking' => $troubles->map(fn($t) => [
                        'equipment' => $t->equipment,
                        'trouble_count' => $t->trouble_count,
                    ])->toArray(),
                    'period' => $period['label'],
                ],
            ];
        }
        
        // Technician productivity
        elseif (preg_match('/(teknisi|technician).*(produktif|terbaik|paling banyak)/i', $question)) {
            $period = self::extractPeriod($question);
            
            $technicians = DB::table('wo_processes as wp')
                ->join('work_orders as wo', 'wp.work_order_id', '=', 'wo.id')
                ->join('users as u', 'wp.performed_by_gpid', '=', 'u.gpid')
                ->select('u.name', DB::raw('COUNT(DISTINCT wo.id) as wo_completed'))
                ->where('wo.status', 'completed')
                ->where('wo.completed_at', '>=', $period['start'])
                ->groupBy('u.gpid', 'u.name')
                ->orderBy('wo_completed', 'desc')
                ->limit(5)
                ->get();
            
            $topTech = $technicians->first();
            $result = [
                'question' => $arguments['question'],
                'answer' => $topTech 
                    ? "Teknisi paling produktif {$period['label']}: {$topTech->name} ({$topTech->wo_completed} WO selesai)"
                    : "Tidak ada data {$period['label']}",
                'details' => [
                    'ranking' => $technicians->toArray(),
                    'period' => $period['label'],
                ],
            ];
        }
        
        // Downtime queries
        elseif (preg_match('/(downtime|waktu mati)/i', $question)) {
            $period = self::extractPeriod($question);
            
            $downtime = DB::table('equipment_troubles')
                ->where('created_at', '>=', $period['start'])
                ->sum('downtime_minutes');
            
            $hours = round($downtime / 60, 1);
            $result = [
                'question' => $arguments['question'],
                'answer' => "Total downtime {$period['label']}: {$hours} jam ({$downtime} menit)",
                'details' => [
                    'total_minutes' => $downtime,
                    'total_hours' => $hours,
                    'period' => $period['label'],
                ],
            ];
        }
        
        // PM compliance
        elseif (preg_match('/(pm|preventive).*(compliance|kepatuhan|rate)/i', $question)) {
            $compliance = PmCompliance::orderBy('created_at', 'desc')->first();
            
            $result = [
                'question' => $arguments['question'],
                'answer' => $compliance 
                    ? "PM Compliance Rate: " . round($compliance->compliance_percentage, 1) . "%"
                    : "Data PM Compliance belum tersedia",
                'details' => $compliance ? [
                    'total_pm' => $compliance->total_pm,
                    'completed' => $compliance->completed_pm,
                    'overdue' => $compliance->overdue_pm,
                    'compliance_rate' => round($compliance->compliance_percentage, 1) . '%',
                ] : null,
            ];
        }
        
        // Stock/Inventory queries
        elseif (preg_match('/(stock|stok|inventory).*(rendah|habis|kosong|low)/i', $question)) {
            $lowStock = Part::whereRaw('current_stock <= minimum_stock')
                ->orderBy('current_stock')
                ->limit(10)
                ->get();
            
            $result = [
                'question' => $arguments['question'],
                'answer' => "Ada {$lowStock->count()} item dengan stock rendah/habis",
                'details' => [
                    'low_stock_items' => $lowStock->map(fn($p) => [
                        'part_number' => $p->part_number,
                        'name' => $p->name,
                        'current_stock' => $p->current_stock,
                        'minimum_stock' => $p->minimum_stock,
                    ])->toArray(),
                ],
            ];
        }
        
        // Default response
        if (!$result) {
            $result = [
                'question' => $arguments['question'],
                'answer' => 'Maaf, saya belum bisa menjawab pertanyaan ini secara otomatis. Coba gunakan pertanyaan yang lebih spesifik seperti:',
                'suggestions' => [
                    'Berapa total WO bulan ini?',
                    'Equipment mana yang paling sering rusak?',
                    'Siapa teknisi yang paling produktif?',
                    'Berapa total downtime minggu ini?',
                    'Bagaimana PM compliance rate?',
                    'Ada stock yang rendah?',
                ],
            ];
        }
        
        return $result;
    }
    
    /**
     * Get comprehensive plant summary
     */
    protected static function getPlantSummary(array $arguments): array
    {
        $detailLevel = $arguments['detail_level'] ?? 'standard';
        $focusArea = $arguments['focus_area'] ?? 'all';
        
        $summary = [
            'generated_at' => now()->format('d-m-Y H:i'),
            'detail_level' => $detailLevel,
        ];
        
        // Equipment Health
        if ($focusArea === 'all' || $focusArea === 'equipment') {
            $activeAssets = \App\Models\Asset::where('is_active', true)->count();
            $recentTroubles = \App\Models\EquipmentTrouble::where('created_at', '>=', now()->subDays(7))->count();
            $openAbnormalities = \App\Models\Abnormality::whereIn('status', ['open', 'in_progress'])->count();
            
            $summary['equipment_health'] = [
                'total_active_assets' => $activeAssets,
                'troubles_last_7_days' => $recentTroubles,
                'open_abnormalities' => $openAbnormalities,
                'status' => $recentTroubles > 10 ? '⚠️ Perlu Perhatian' : '✅ Normal',
            ];
        }
        
        // Maintenance Status
        if ($focusArea === 'all' || $focusArea === 'maintenance') {
            $openWo = WorkOrder::where('status', 'open')->count();
            $inProgressWo = WorkOrder::where('status', 'in_progress')->count();
            $criticalWo = WorkOrder::where('priority', 'critical')->whereIn('status', ['open', 'in_progress'])->count();
            
            $overduePm = PmSchedule::where('is_active', true)
                ->where('next_due_date', '<', now())
                ->count();
            
            $dueSoonPm = PmSchedule::where('is_active', true)
                ->whereBetween('next_due_date', [now(), now()->addDays(7)])
                ->count();
            
            $summary['maintenance_status'] = [
                'open_work_orders' => $openWo,
                'in_progress_work_orders' => $inProgressWo,
                'critical_work_orders' => $criticalWo,
                'overdue_pm' => $overduePm,
                'pm_due_in_7_days' => $dueSoonPm,
                'status' => ($criticalWo > 0 || $overduePm > 5) ? '⚠️ Perlu Perhatian' : '✅ Normal',
            ];
        }
        
        // Inventory Status
        if ($focusArea === 'all' || $focusArea === 'inventory') {
            $lowStock = Part::whereRaw('current_stock <= minimum_stock')->count();
            $outOfStock = Part::where('current_stock', 0)->count();
            $activeAlerts = StockAlert::where('is_resolved', false)->count();
            
            $summary['inventory_status'] = [
                'low_stock_items' => $lowStock,
                'out_of_stock_items' => $outOfStock,
                'active_alerts' => $activeAlerts,
                'status' => ($outOfStock > 0 || $lowStock > 10) ? '⚠️ Perlu Perhatian' : '✅ Normal',
            ];
        }
        
        // Production (if applicable)
        if ($focusArea === 'all' || $focusArea === 'production') {
            $todayProduction = \App\Models\ProductionRecord::whereDate('production_date', today())->first();
            $utilityToday = \App\Models\UtilityConsumption::whereDate('record_date', today())->first();
            
            $summary['production_status'] = [
                'today_production' => $todayProduction ? [
                    'actual_qty' => $todayProduction->actual_qty ?? 0,
                    'good_qty' => $todayProduction->good_qty ?? 0,
                ] : 'Belum ada data hari ini',
                'utility_consumption' => $utilityToday ? [
                    'electricity_kwh' => $utilityToday->electricity_kwh ?? 0,
                    'water_m3' => $utilityToday->water_m3 ?? 0,
                ] : 'Belum ada data hari ini',
            ];
        }
        
        // KPI Overview (for standard and detailed)
        if ($detailLevel !== 'brief') {
            $thisMonth = now()->startOfMonth();
            $woThisMonth = WorkOrder::where('created_at', '>=', $thisMonth)->count();
            $woCompletedThisMonth = WorkOrder::where('completed_at', '>=', $thisMonth)->count();
            
            $summary['kpi_overview'] = [
                'wo_created_this_month' => $woThisMonth,
                'wo_completed_this_month' => $woCompletedThisMonth,
                'wo_completion_rate' => $woThisMonth > 0 ? round(($woCompletedThisMonth / $woThisMonth) * 100, 1) . '%' : 'N/A',
            ];
        }
        
        // Action Priorities (for detailed)
        if ($detailLevel === 'detailed') {
            $priorities = [];
            
            // Critical WOs
            $criticalWos = WorkOrder::where('priority', 'critical')
                ->whereIn('status', ['open', 'in_progress'])
                ->with(['subAsset.asset'])
                ->limit(5)
                ->get();
            
            foreach ($criticalWos as $wo) {
                $priorities[] = [
                    'type' => 'Critical WO',
                    'description' => $wo->wo_number . ' - ' . ($wo->subAsset?->asset?->name ?? 'Unknown'),
                    'urgency' => '🔴 Critical',
                ];
            }
            
            // Overdue PMs
            $overduePms = PmSchedule::where('is_active', true)
                ->where('next_due_date', '<', now())
                ->with(['subAsset.asset'])
                ->limit(5)
                ->get();
            
            foreach ($overduePms as $pm) {
                $priorities[] = [
                    'type' => 'Overdue PM',
                    'description' => ($pm->subAsset?->asset?->name ?? 'Unknown') . ' - ' . $pm->task_name,
                    'urgency' => '🟠 High',
                ];
            }
            
            // Out of stock items
            $outOfStockItems = Part::where('current_stock', 0)->limit(5)->get();
            foreach ($outOfStockItems as $part) {
                $priorities[] = [
                    'type' => 'Out of Stock',
                    'description' => $part->name . ' (' . $part->part_number . ')',
                    'urgency' => '🟡 Medium',
                ];
            }
            
            $summary['action_priorities'] = $priorities;
        }
        
        // Overall Status
        $issues = 0;
        if (isset($summary['equipment_health']) && str_contains($summary['equipment_health']['status'], 'Perhatian')) $issues++;
        if (isset($summary['maintenance_status']) && str_contains($summary['maintenance_status']['status'], 'Perhatian')) $issues++;
        if (isset($summary['inventory_status']) && str_contains($summary['inventory_status']['status'], 'Perhatian')) $issues++;
        
        $summary['overall_status'] = match(true) {
            $issues >= 3 => '🔴 CRITICAL - Banyak area memerlukan perhatian segera',
            $issues >= 2 => '🟠 WARNING - Beberapa area memerlukan perhatian',
            $issues >= 1 => '🟡 CAUTION - Ada area yang perlu diperhatikan',
            default => '✅ GOOD - Semua area dalam kondisi normal',
        };
        
        return $summary;
    }
    
    /**
     * Helper: Extract period from question
     */
    private static function extractPeriod(string $question): array
    {
        if (preg_match('/hari ini|today/i', $question)) {
            return ['start' => now()->startOfDay(), 'label' => 'hari ini'];
        }
        if (preg_match('/kemarin|yesterday/i', $question)) {
            return ['start' => now()->subDay()->startOfDay(), 'label' => 'kemarin'];
        }
        if (preg_match('/minggu ini|this week/i', $question)) {
            return ['start' => now()->startOfWeek(), 'label' => 'minggu ini'];
        }
        if (preg_match('/minggu lalu|last week/i', $question)) {
            return ['start' => now()->subWeek()->startOfWeek(), 'label' => 'minggu lalu'];
        }
        if (preg_match('/bulan ini|this month/i', $question)) {
            return ['start' => now()->startOfMonth(), 'label' => 'bulan ini'];
        }
        if (preg_match('/bulan lalu|last month/i', $question)) {
            return ['start' => now()->subMonth()->startOfMonth(), 'label' => 'bulan lalu'];
        }
        if (preg_match('/tahun ini|this year/i', $question)) {
            return ['start' => now()->startOfYear(), 'label' => 'tahun ini'];
        }
        
        // Default to this month
        return ['start' => now()->startOfMonth(), 'label' => 'bulan ini'];
    }
    
    /**
     * Helper: Apply period to query
     */
    private static function applyPeriodToQuery($query, array $period)
    {
        return $query->where('created_at', '>=', $period['start']);
    }
}
