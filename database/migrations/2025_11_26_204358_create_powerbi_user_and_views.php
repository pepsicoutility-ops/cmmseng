<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Power BI read-only user
        DB::statement("DROP USER IF EXISTS 'powerbi_readonly'@'%'");
        DB::statement("CREATE USER 'powerbi_readonly'@'%' IDENTIFIED BY 'PowerBI@2025'");
        DB::statement("GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'%'");
        DB::statement("FLUSH PRIVILEGES");

        // Create Power BI optimized views
        
        // 1. Work Orders Analysis View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_work_orders AS
            SELECT 
                -- Work Order Details
                wo.id AS wo_id,
                wo.wo_number,
                wo.operator_name,
                wo.shift,
                wo.problem_type,
                wo.priority,
                wo.status,
                wo.assign_to AS department,
                wo.description,
                wo.mttr AS mttr_minutes,
                ROUND(wo.mttr / 60.0, 2) AS mttr_hours,
                wo.total_downtime AS downtime_minutes,
                ROUND(wo.total_downtime / 60.0, 2) AS downtime_hours,
                
                -- Dates
                wo.created_at AS submitted_date,
                DATE(wo.created_at) AS submitted_date_only,
                wo.reviewed_at,
                wo.approved_at,
                wo.started_at,
                wo.completed_at,
                wo.closed_at,
                
                -- Time Calculations
                TIMESTAMPDIFF(HOUR, wo.created_at, wo.completed_at) AS resolution_hours,
                TIMESTAMPDIFF(DAY, wo.created_at, wo.completed_at) AS resolution_days,
                
                -- Equipment Hierarchy
                a.id AS area_id,
                a.name AS area_name,
                sa.id AS sub_area_id,
                sa.name AS sub_area_name,
                ast.id AS asset_id,
                ast.name AS asset_name,
                ast.code AS asset_code,
                
                -- User Details
                u.gpid AS created_by_gpid,
                u.name AS created_by_name,
                u.role AS created_by_role,
                
                -- Cost Details
                wc.labour_cost,
                wc.parts_cost,
                wc.downtime_cost,
                wc.total_cost,
                
                -- Status Flags
                CASE WHEN wo.status = 'completed' THEN 1 ELSE 0 END AS is_completed,
                CASE WHEN wo.status = 'closed' THEN 1 ELSE 0 END AS is_closed,
                CASE WHEN wo.status IN ('submitted', 'reviewed', 'approved', 'in_progress') THEN 1 ELSE 0 END AS is_open,
                
                -- Time Period Indicators
                YEAR(wo.created_at) AS `year`,
                MONTH(wo.created_at) AS `month`,
                QUARTER(wo.created_at) AS `quarter`,
                DATE_FORMAT(wo.created_at, '%Y-%m') AS `year_month`
                
            FROM work_orders wo
            LEFT JOIN areas a ON wo.area_id = a.id
            LEFT JOIN sub_areas sa ON wo.sub_area_id = sa.id
            LEFT JOIN assets ast ON wo.asset_id = ast.id
            LEFT JOIN users u ON wo.created_by_gpid = u.gpid
            LEFT JOIN wo_costs wc ON wo.id = wc.work_order_id
            WHERE wo.deleted_at IS NULL
        ");

        // 2. PM Compliance View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_pm_compliance AS
            SELECT 
                -- PM Execution Details
                pe.id AS pm_execution_id,
                pe.scheduled_date,
                DATE(pe.scheduled_date) AS scheduled_date_only,
                pe.actual_start,
                pe.actual_end,
                pe.duration AS duration_minutes,
                ROUND(pe.duration / 60.0, 2) AS duration_hours,
                pe.status,
                pe.is_on_time,
                
                -- PM Schedule Details
                ps.id AS pm_schedule_id,
                ps.code AS pm_code,
                ps.title AS pm_title,
                ps.schedule_type,
                ps.frequency,
                ps.department,
                
                -- Equipment Hierarchy
                a.id AS area_id,
                a.name AS area_name,
                ast.id AS asset_id,
                ast.name AS asset_name,
                
                -- Technician Details
                u.gpid AS technician_gpid,
                u.name AS technician_name,
                
                -- Cost Details
                pc.labour_cost,
                pc.parts_cost,
                pc.overhead_cost,
                pc.total_cost,
                
                -- Compliance Indicators
                CASE WHEN pe.is_on_time = 1 THEN 'On Time' ELSE 'Late' END AS compliance_status,
                CASE WHEN pe.status = 'completed' THEN 1 ELSE 0 END AS is_completed,
                
                -- Time Period Indicators
                YEAR(pe.scheduled_date) AS `year`,
                MONTH(pe.scheduled_date) AS `month`,
                QUARTER(pe.scheduled_date) AS `quarter`,
                DATE_FORMAT(pe.scheduled_date, '%Y-%m') AS `year_month`
                
            FROM pm_executions pe
            INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
            LEFT JOIN areas a ON ps.area_id = a.id
            LEFT JOIN assets ast ON ps.asset_id = ast.id
            LEFT JOIN users u ON pe.executed_by_gpid = u.gpid
            LEFT JOIN pm_costs pc ON pe.id = pc.pm_execution_id
            WHERE pe.deleted_at IS NULL
        ");

        // 3. Inventory View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_inventory AS
            SELECT 
                -- Part Details
                p.id AS part_id,
                p.part_number,
                p.name AS part_name,
                p.category,
                p.unit,
                p.current_stock,
                p.min_stock,
                p.unit_price,
                p.location,
                
                -- Stock Calculations
                p.current_stock * p.unit_price AS stock_value,
                p.current_stock - p.min_stock AS stock_buffer,
                
                -- Stock Status
                CASE 
                    WHEN p.current_stock = 0 THEN 'Out of Stock'
                    WHEN p.current_stock <= p.min_stock THEN 'Low Stock'
                    WHEN p.current_stock <= p.min_stock * 1.5 THEN 'Warning'
                    ELSE 'Sufficient'
                END AS stock_status,
                
                -- Last Movement Date
                (SELECT MAX(im.created_at) 
                 FROM inventory_movements im 
                 WHERE im.part_id = p.id) AS last_movement_date,
                
                p.created_at,
                p.updated_at
                
            FROM parts p
            WHERE p.deleted_at IS NULL
        ");

        // 4. Equipment Performance View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_equipment AS
            SELECT 
                -- Asset Details
                a.id AS asset_id,
                a.name AS asset_name,
                a.code AS asset_code,
                a.model,
                
                -- Hierarchy
                sa.id AS sub_area_id,
                sa.name AS sub_area_name,
                ar.id AS area_id,
                ar.name AS area_name,
                
                -- Work Order Metrics
                (SELECT COUNT(*) 
                 FROM work_orders wo 
                 WHERE wo.asset_id = a.id AND wo.deleted_at IS NULL) AS total_wo,
                
                (SELECT COUNT(*) 
                 FROM work_orders wo 
                 WHERE wo.asset_id = a.id 
                 AND wo.status IN ('submitted', 'reviewed', 'approved', 'in_progress') 
                 AND wo.deleted_at IS NULL) AS open_wo,
                
                (SELECT AVG(wo.mttr) 
                 FROM work_orders wo 
                 WHERE wo.asset_id = a.id 
                 AND wo.mttr IS NOT NULL 
                 AND wo.deleted_at IS NULL) AS avg_mttr_minutes,
                
                -- PM Metrics
                (SELECT COUNT(*) 
                 FROM pm_schedules ps 
                 WHERE ps.asset_id = a.id 
                 AND ps.deleted_at IS NULL) AS total_pm_schedules,
                
                (SELECT COUNT(*) 
                 FROM pm_schedules ps 
                 INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
                 WHERE ps.asset_id = a.id 
                 AND pe.is_on_time = 1 
                 AND pe.deleted_at IS NULL) AS on_time_pm
                
            FROM assets a
            LEFT JOIN sub_areas sa ON a.sub_area_id = sa.id
            LEFT JOIN areas ar ON sa.area_id = ar.id
            WHERE a.deleted_at IS NULL
        ");

        // 5. Cost Analysis View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_costs AS
            SELECT 
                'Work Order' AS cost_type,
                wo.wo_number AS reference_number,
                wo.assign_to AS department,
                wo.completed_at AS completion_date,
                DATE(wo.completed_at) AS completion_date_only,
                wc.labour_cost,
                wc.parts_cost,
                wc.downtime_cost AS additional_cost,
                wc.total_cost,
                a.name AS asset_name,
                
                -- Time Period
                YEAR(wo.completed_at) AS `year`,
                MONTH(wo.completed_at) AS `month`,
                QUARTER(wo.completed_at) AS `quarter`,
                DATE_FORMAT(wo.completed_at, '%Y-%m') AS `year_month`
                
            FROM wo_costs wc
            INNER JOIN work_orders wo ON wc.work_order_id = wo.id
            LEFT JOIN assets a ON wo.asset_id = a.id
            WHERE wo.deleted_at IS NULL

            UNION ALL

            SELECT 
                'Preventive Maintenance' AS cost_type,
                ps.code AS reference_number,
                ps.department,
                pe.actual_end AS completion_date,
                DATE(pe.actual_end) AS completion_date_only,
                pc.labour_cost,
                pc.parts_cost,
                pc.overhead_cost AS additional_cost,
                pc.total_cost,
                a.name AS asset_name,
                
                -- Time Period
                YEAR(pe.actual_end) AS `year`,
                MONTH(pe.actual_end) AS `month`,
                QUARTER(pe.actual_end) AS `quarter`,
                DATE_FORMAT(pe.actual_end, '%Y-%m') AS `year_month`
                
            FROM pm_costs pc
            INNER JOIN pm_executions pe ON pc.pm_execution_id = pe.id
            INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
            LEFT JOIN assets a ON ps.asset_id = a.id
            WHERE pe.deleted_at IS NULL
        ");

        // 6. Technician Performance View
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_technician_performance AS
            SELECT 
                u.id AS user_id,
                u.gpid,
                u.name AS technician_name,
                u.department,
                
                -- PM Metrics
                (SELECT COUNT(*) 
                 FROM pm_executions pe 
                 WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) AS total_pm,
                
                (SELECT COUNT(*) 
                 FROM pm_executions pe 
                 WHERE pe.executed_by_gpid = u.gpid 
                 AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) AS on_time_pm,
                
                -- Compliance Percentage
                ROUND(
                    (SELECT COUNT(*) 
                     FROM pm_executions pe 
                     WHERE pe.executed_by_gpid = u.gpid AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) * 100.0 /
                    NULLIF((SELECT COUNT(*) 
                            FROM pm_executions pe 
                            WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL), 0),
                    2
                ) AS pm_compliance_percentage
                
            FROM users u
            WHERE u.role = 'technician' 
            AND u.is_active = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_work_orders");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_pm_compliance");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_inventory");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_equipment");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_costs");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_technician_performance");
        
        // Drop user
        DB::statement("DROP USER IF EXISTS 'powerbi_readonly'@'%'");
        DB::statement("FLUSH PRIVILEGES");
    }
};
