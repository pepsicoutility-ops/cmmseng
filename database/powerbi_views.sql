-- ================================================================
-- Power BI Optimized Database Views
-- PepsiCo Engineering CMMS
-- Database: cmmseng
-- Created: November 26, 2025
-- ================================================================

-- These views pre-join tables and calculate metrics to improve
-- Power BI performance and simplify data model.

-- ================================================================
-- 1. Work Orders Analysis View
-- ================================================================

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
    TIMESTAMPDIFF(MINUTE, wo.started_at, wo.completed_at) AS work_duration_minutes,
    
    -- Equipment Hierarchy
    a.id AS area_id,
    a.name AS area_name,
    sa.id AS sub_area_id,
    sa.name AS sub_area_name,
    ast.id AS asset_id,
    ast.name AS asset_name,
    ast.code AS asset_code,
    ast.model AS asset_model,
    sast.id AS sub_asset_id,
    sast.name AS sub_asset_name,
    
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
    CASE WHEN wo.status IN ('submitted', 'reviewed', 'approved', 'in_progress', 'on_hold') THEN 1 ELSE 0 END AS is_open,
    
    -- Time Period Indicators
    YEAR(wo.created_at) AS year,
    MONTH(wo.created_at) AS month,
    QUARTER(wo.created_at) AS quarter,
    WEEK(wo.created_at) AS week_number,
    DATE_FORMAT(wo.created_at, '%Y-%m') AS year_month,
    DATE_FORMAT(wo.created_at, '%Y-Q%q') AS year_quarter
    
FROM work_orders wo
LEFT JOIN areas a ON wo.area_id = a.id
LEFT JOIN sub_areas sa ON wo.sub_area_id = sa.id
LEFT JOIN assets ast ON wo.asset_id = ast.id
LEFT JOIN sub_assets sast ON wo.sub_asset_id = sast.id
LEFT JOIN users u ON wo.created_by_gpid = u.gpid
LEFT JOIN wo_costs wc ON wo.id = wc.work_order_id
WHERE wo.deleted_at IS NULL;

-- ================================================================
-- 2. PM Compliance View
-- ================================================================

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
    pe.notes,
    
    -- PM Schedule Details
    ps.id AS pm_schedule_id,
    ps.code AS pm_code,
    ps.title AS pm_title,
    ps.description AS pm_description,
    ps.schedule_type,
    ps.frequency,
    ps.week_day,
    ps.department,
    ps.estimated_duration AS estimated_minutes,
    
    -- Equipment Hierarchy
    a.id AS area_id,
    a.name AS area_name,
    sa.id AS sub_area_id,
    sa.name AS sub_area_name,
    ast.id AS asset_id,
    ast.name AS asset_name,
    ast.code AS asset_code,
    ast.model AS asset_model,
    sast.id AS sub_asset_id,
    sast.name AS sub_asset_name,
    
    -- Technician Details
    u.gpid AS technician_gpid,
    u.name AS technician_name,
    u.department AS technician_department,
    
    -- Assignment Details
    u2.gpid AS assigned_by_gpid,
    u2.name AS assigned_by_name,
    
    -- Cost Details
    pc.labour_cost,
    pc.parts_cost,
    pc.overhead_cost,
    pc.total_cost,
    
    -- Compliance Indicators
    CASE WHEN pe.is_on_time = 1 THEN 'On Time' ELSE 'Late' END AS compliance_status,
    CASE WHEN pe.status = 'completed' THEN 1 ELSE 0 END AS is_completed,
    CASE WHEN pe.is_on_time = 1 AND pe.status = 'completed' THEN 1 ELSE 0 END AS is_compliant,
    
    -- Time Variance
    TIMESTAMPDIFF(HOUR, pe.scheduled_date, pe.actual_end) AS variance_hours,
    TIMESTAMPDIFF(DAY, pe.scheduled_date, pe.actual_end) AS variance_days,
    
    -- Time Period Indicators
    YEAR(pe.scheduled_date) AS year,
    MONTH(pe.scheduled_date) AS month,
    QUARTER(pe.scheduled_date) AS quarter,
    WEEK(pe.scheduled_date) AS week_number,
    DATE_FORMAT(pe.scheduled_date, '%Y-%m') AS year_month,
    DATE_FORMAT(pe.scheduled_date, '%Y-Q%q') AS year_quarter
    
FROM pm_executions pe
INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
LEFT JOIN areas a ON ps.area_id = a.id
LEFT JOIN sub_areas sa ON ps.sub_area_id = sa.id
LEFT JOIN assets ast ON ps.asset_id = ast.id
LEFT JOIN sub_assets sast ON ps.sub_asset_id = sast.id
LEFT JOIN users u ON pe.executed_by_gpid = u.gpid
LEFT JOIN users u2 ON ps.assigned_by_gpid = u2.gpid
LEFT JOIN pm_costs pc ON pe.id = pc.pm_execution_id
WHERE pe.deleted_at IS NULL;

-- ================================================================
-- 3. Inventory & Stock View
-- ================================================================

CREATE OR REPLACE VIEW vw_powerbi_inventory AS
SELECT 
    -- Part Details
    p.id AS part_id,
    p.part_number,
    p.name AS part_name,
    p.description,
    p.category,
    p.unit,
    p.current_stock,
    p.min_stock,
    p.unit_price,
    p.location,
    p.last_restocked_at,
    
    -- Stock Calculations
    p.current_stock * p.unit_price AS stock_value,
    p.current_stock - p.min_stock AS stock_buffer,
    ROUND((p.current_stock - p.min_stock) * 100.0 / p.min_stock, 2) AS buffer_percentage,
    
    -- Stock Status
    CASE 
        WHEN p.current_stock = 0 THEN 'Out of Stock'
        WHEN p.current_stock <= p.min_stock THEN 'Low Stock'
        WHEN p.current_stock <= p.min_stock * 1.5 THEN 'Warning'
        ELSE 'Sufficient'
    END AS stock_status,
    
    -- Stock Level Indicator (numeric for calculations)
    CASE 
        WHEN p.current_stock = 0 THEN 0
        WHEN p.current_stock <= p.min_stock THEN 1
        WHEN p.current_stock <= p.min_stock * 1.5 THEN 2
        ELSE 3
    END AS stock_level,
    
    -- Inventory Count by Location
    (SELECT COUNT(*) 
     FROM inventories i 
     WHERE i.part_id = p.id AND i.deleted_at IS NULL) AS location_count,
    
    -- Total Inventory Quantity (across all locations)
    COALESCE((SELECT SUM(i.quantity) 
              FROM inventories i 
              WHERE i.part_id = p.id AND i.deleted_at IS NULL), 0) AS total_inventory_qty,
    
    -- Active Alerts
    (SELECT COUNT(*) 
     FROM stock_alerts sa 
     WHERE sa.part_id = p.id AND sa.is_resolved = 0) AS active_alerts,
    
    -- Last Movement Date
    (SELECT MAX(im.created_at) 
     FROM inventory_movements im 
     WHERE im.part_id = p.id) AS last_movement_date,
    
    -- Usage Metrics (last 30 days)
    (SELECT COALESCE(SUM(im.quantity), 0)
     FROM inventory_movements im 
     WHERE im.part_id = p.id 
     AND im.movement_type = 'out' 
     AND im.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS usage_last_30_days,
    
    -- Created/Updated Dates
    p.created_at,
    p.updated_at
    
FROM parts p
WHERE p.deleted_at IS NULL;

-- ================================================================
-- 4. Equipment Performance View
-- ================================================================

CREATE OR REPLACE VIEW vw_powerbi_equipment AS
SELECT 
    -- Asset Details
    a.id AS asset_id,
    a.name AS asset_name,
    a.code AS asset_code,
    a.model,
    a.serial_number,
    a.installation_date,
    a.is_active,
    
    -- Hierarchy
    ar.id AS area_id,
    ar.name AS area_name,
    sa.id AS sub_area_id,
    sa.name AS sub_area_name,
    
    -- Work Order Metrics
    (SELECT COUNT(*) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id AND wo.deleted_at IS NULL) AS total_wo,
    
    (SELECT COUNT(*) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id 
     AND wo.status IN ('submitted', 'reviewed', 'approved', 'in_progress') 
     AND wo.deleted_at IS NULL) AS open_wo,
    
    (SELECT COUNT(*) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id 
     AND wo.status = 'completed' 
     AND wo.deleted_at IS NULL) AS completed_wo,
    
    (SELECT AVG(wo.mttr) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id 
     AND wo.mttr IS NOT NULL 
     AND wo.deleted_at IS NULL) AS avg_mttr_minutes,
    
    (SELECT SUM(wo.total_downtime) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id 
     AND wo.total_downtime IS NOT NULL 
     AND wo.deleted_at IS NULL) AS total_downtime_minutes,
    
    -- PM Metrics
    (SELECT COUNT(*) 
     FROM pm_schedules ps 
     WHERE ps.asset_id = a.id 
     AND ps.deleted_at IS NULL) AS total_pm_schedules,
    
    (SELECT COUNT(*) 
     FROM pm_schedules ps 
     INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
     WHERE ps.asset_id = a.id 
     AND pe.deleted_at IS NULL) AS total_pm_executed,
    
    (SELECT COUNT(*) 
     FROM pm_schedules ps 
     INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
     WHERE ps.asset_id = a.id 
     AND pe.is_on_time = 1 
     AND pe.deleted_at IS NULL) AS on_time_pm,
    
    -- Last Maintenance Date
    (SELECT MAX(pe.actual_end) 
     FROM pm_schedules ps 
     INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
     WHERE ps.asset_id = a.id 
     AND pe.status = 'completed') AS last_pm_date,
    
    (SELECT MAX(wo.completed_at) 
     FROM work_orders wo 
     WHERE wo.asset_id = a.id 
     AND wo.status = 'completed') AS last_wo_date,
    
    -- Cost Totals
    (SELECT COALESCE(SUM(wc.total_cost), 0)
     FROM work_orders wo
     INNER JOIN wo_costs wc ON wo.id = wc.work_order_id
     WHERE wo.asset_id = a.id AND wo.deleted_at IS NULL) AS total_wo_cost,
    
    (SELECT COALESCE(SUM(pc.total_cost), 0)
     FROM pm_schedules ps
     INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id
     INNER JOIN pm_costs pc ON pe.id = pc.pm_execution_id
     WHERE ps.asset_id = a.id AND pe.deleted_at IS NULL) AS total_pm_cost,
    
    -- Reliability Metrics
    ROUND(
        (SELECT COUNT(*) 
         FROM pm_schedules ps 
         INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
         WHERE ps.asset_id = a.id AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) * 100.0 /
        NULLIF((SELECT COUNT(*) 
                FROM pm_schedules ps 
                INNER JOIN pm_executions pe ON ps.id = pe.pm_schedule_id 
                WHERE ps.asset_id = a.id AND pe.deleted_at IS NULL), 0),
        2
    ) AS pm_compliance_percentage
    
FROM assets a
LEFT JOIN areas ar ON a.area_id = ar.id
LEFT JOIN sub_areas sa ON a.sub_area_id = sa.id
WHERE a.deleted_at IS NULL;

-- ================================================================
-- 5. Cost Analysis View
-- ================================================================

CREATE OR REPLACE VIEW vw_powerbi_costs AS
SELECT 
    'Work Order' AS cost_type,
    wo.wo_number AS reference_number,
    wo.id AS reference_id,
    wo.problem_type,
    wo.priority,
    wo.assign_to AS department,
    wo.completed_at AS completion_date,
    DATE(wo.completed_at) AS completion_date_only,
    wc.labour_cost,
    wc.parts_cost,
    wc.downtime_cost AS additional_cost,
    wc.total_cost,
    a.name AS asset_name,
    ar.name AS area_name,
    
    -- Time Period
    YEAR(wo.completed_at) AS year,
    MONTH(wo.completed_at) AS month,
    QUARTER(wo.completed_at) AS quarter,
    DATE_FORMAT(wo.completed_at, '%Y-%m') AS year_month
    
FROM wo_costs wc
INNER JOIN work_orders wo ON wc.work_order_id = wo.id
LEFT JOIN assets a ON wo.asset_id = a.id
LEFT JOIN areas ar ON wo.area_id = ar.id
WHERE wo.deleted_at IS NULL

UNION ALL

SELECT 
    'Preventive Maintenance' AS cost_type,
    ps.code AS reference_number,
    pe.id AS reference_id,
    ps.schedule_type AS problem_type,
    NULL AS priority,
    ps.department,
    pe.actual_end AS completion_date,
    DATE(pe.actual_end) AS completion_date_only,
    pc.labour_cost,
    pc.parts_cost,
    pc.overhead_cost AS additional_cost,
    pc.total_cost,
    a.name AS asset_name,
    ar.name AS area_name,
    
    -- Time Period
    YEAR(pe.actual_end) AS year,
    MONTH(pe.actual_end) AS month,
    QUARTER(pe.actual_end) AS quarter,
    DATE_FORMAT(pe.actual_end, '%Y-%m') AS year_month
    
FROM pm_costs pc
INNER JOIN pm_executions pe ON pc.pm_execution_id = pe.id
INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
LEFT JOIN assets a ON ps.asset_id = a.id
LEFT JOIN areas ar ON ps.area_id = ar.id
WHERE pe.deleted_at IS NULL;

-- ================================================================
-- 6. Technician Performance View
-- ================================================================

CREATE OR REPLACE VIEW vw_powerbi_technician_performance AS
SELECT 
    u.id AS user_id,
    u.gpid,
    u.name AS technician_name,
    u.department,
    u.is_active,
    
    -- PM Metrics
    (SELECT COUNT(*) 
     FROM pm_executions pe 
     WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) AS total_pm,
    
    (SELECT COUNT(*) 
     FROM pm_executions pe 
     WHERE pe.executed_by_gpid = u.gpid 
     AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) AS on_time_pm,
    
    (SELECT COUNT(*) 
     FROM pm_executions pe 
     WHERE pe.executed_by_gpid = u.gpid 
     AND pe.is_on_time = 0 AND pe.deleted_at IS NULL) AS late_pm,
    
    (SELECT AVG(pe.duration) 
     FROM pm_executions pe 
     WHERE pe.executed_by_gpid = u.gpid 
     AND pe.duration IS NOT NULL AND pe.deleted_at IS NULL) AS avg_pm_duration,
    
    -- WO Metrics
    (SELECT COUNT(DISTINCT wo.id) 
     FROM work_orders wo
     INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
     WHERE wp.performed_by_gpid = u.gpid AND wo.deleted_at IS NULL) AS total_wo,
    
    (SELECT COUNT(DISTINCT wo.id) 
     FROM work_orders wo
     INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
     WHERE wp.performed_by_gpid = u.gpid 
     AND wo.status = 'completed' AND wo.deleted_at IS NULL) AS completed_wo,
    
    (SELECT AVG(wo.mttr) 
     FROM work_orders wo
     INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
     WHERE wp.performed_by_gpid = u.gpid 
     AND wo.mttr IS NOT NULL AND wo.deleted_at IS NULL) AS avg_mttr,
    
    -- Compliance Percentage
    ROUND(
        (SELECT COUNT(*) 
         FROM pm_executions pe 
         WHERE pe.executed_by_gpid = u.gpid AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) * 100.0 /
        NULLIF((SELECT COUNT(*) 
                FROM pm_executions pe 
                WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL), 0),
        2
    ) AS pm_compliance_percentage,
    
    -- Performance Score (based on Phase 13.5 logic)
    LEAST(
        -- PM Compliance Score (40 points)
        ROUND(
            (SELECT COUNT(*) 
             FROM pm_executions pe 
             WHERE pe.executed_by_gpid = u.gpid AND pe.is_on_time = 1 AND pe.deleted_at IS NULL) * 40.0 /
            NULLIF((SELECT COUNT(*) 
                    FROM pm_executions pe 
                    WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL), 0),
            2
        ) +
        -- Workload Score (30 points)
        CASE 
            WHEN (SELECT COUNT(*) 
                  FROM pm_executions pe 
                  WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) +
                 (SELECT COUNT(DISTINCT wo.id) 
                  FROM work_orders wo
                  INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
                  WHERE wp.performed_by_gpid = u.gpid AND wo.deleted_at IS NULL) >= 20 THEN 30
            WHEN (SELECT COUNT(*) 
                  FROM pm_executions pe 
                  WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) +
                 (SELECT COUNT(DISTINCT wo.id) 
                  FROM work_orders wo
                  INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
                  WHERE wp.performed_by_gpid = u.gpid AND wo.deleted_at IS NULL) >= 10 THEN 20
            WHEN (SELECT COUNT(*) 
                  FROM pm_executions pe 
                  WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) +
                 (SELECT COUNT(DISTINCT wo.id) 
                  FROM work_orders wo
                  INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
                  WHERE wp.performed_by_gpid = u.gpid AND wo.deleted_at IS NULL) >= 5 THEN 10
            ELSE 5
        END +
        -- Activity Score (30 points)
        CASE 
            WHEN (SELECT COUNT(*) 
                  FROM pm_executions pe 
                  WHERE pe.executed_by_gpid = u.gpid AND pe.deleted_at IS NULL) +
                 (SELECT COUNT(DISTINCT wo.id) 
                  FROM work_orders wo
                  INNER JOIN wo_processes wp ON wo.id = wp.work_order_id
                  WHERE wp.performed_by_gpid = u.gpid AND wo.deleted_at IS NULL) > 0 THEN 30
            ELSE 0
        END,
        100
    ) AS performance_score
    
FROM users u
WHERE u.role = 'technician' 
AND u.deleted_at IS NULL;

-- ================================================================
-- GRANT SELECT on Views to Power BI User
-- ================================================================

GRANT SELECT ON cmmseng.vw_powerbi_work_orders TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.vw_powerbi_pm_compliance TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.vw_powerbi_inventory TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.vw_powerbi_equipment TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.vw_powerbi_costs TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.vw_powerbi_technician_performance TO 'powerbi_readonly'@'%';

FLUSH PRIVILEGES;

-- ================================================================
-- VERIFY VIEWS
-- ================================================================

-- Test each view
SELECT COUNT(*) AS work_orders_count FROM vw_powerbi_work_orders;
SELECT COUNT(*) AS pm_compliance_count FROM vw_powerbi_pm_compliance;
SELECT COUNT(*) AS inventory_count FROM vw_powerbi_inventory;
SELECT COUNT(*) AS equipment_count FROM vw_powerbi_equipment;
SELECT COUNT(*) AS costs_count FROM vw_powerbi_costs;
SELECT COUNT(*) AS technician_count FROM vw_powerbi_technician_performance;

-- ================================================================
-- NOTES
-- ================================================================

-- 1. These views are optimized for Power BI Import mode
-- 2. Use these views instead of joining tables in Power BI for better performance
-- 3. Views automatically filter soft-deleted records (deleted_at IS NULL)
-- 4. All calculations are pre-computed to reduce Power BI processing
-- 5. Time period columns (year, month, quarter) are included for easy filtering
-- 6. Refresh Power BI dataset after creating/modifying views

-- ================================================================
-- END OF SCRIPT
-- ================================================================
