# 📊 Power BI Integration Guide - PepsiCo CMMS

**Application:** PepsiCo Engineering CMMS  
**Version:** 1.0  
**Database:** MySQL 8.0  
**Last Updated:** November 26, 2025

---

## 📑 Table of Contents

1. [Overview](#overview)
2. [Integration Options](#integration-options)
3. [Database Direct Connection (Recommended)](#database-direct-connection-recommended)
4. [API Integration (Alternative)](#api-integration-alternative)
5. [CSV Export Integration](#csv-export-integration)
6. [Database Schema for Reporting](#database-schema-for-reporting)
7. [Security Configuration](#security-configuration)
8. [Power BI Data Model](#power-bi-data-model)
9. [Sample DAX Measures](#sample-dax-measures)
10. [Publishing & Refresh](#publishing--refresh)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

This guide explains how to integrate **Power BI** with the PepsiCo CMMS application to create interactive dashboards and reports. Power BI will pull data directly from the MySQL database to visualize:

- Work Order metrics (MTTR, downtime, by department/problem type)
- PM Compliance tracking
- Inventory stock levels and movements
- Cost analysis (PM costs, WO costs, parts costs)
- Equipment performance and reliability
- Technician performance metrics

### Integration Architecture

```
┌─────────────────┐         ┌──────────────┐         ┌─────────────────┐
│                 │         │              │         │                 │
│  CMMS Laravel   │────────▶│   MySQL      │────────▶│   Power BI      │
│  Application    │         │   Database   │         │   Desktop/      │
│  (Filament)     │         │  (cmmseng)   │         │   Service       │
│                 │         │              │         │                 │
└─────────────────┘         └──────────────┘         └─────────────────┘
        │                           │
        │                           │
        ▼                           ▼
   (Users interact)         (Read-only access)
                                   │
                                   ▼
                          ┌──────────────────┐
                          │  Power BI        │
                          │  Dashboards      │
                          │  (Published)     │
                          └──────────────────┘
```

---

## 🔌 Integration Options

### Option 1: Database Direct Connection ⭐ **RECOMMENDED**

**Pros:**
- ✅ Real-time or near real-time data
- ✅ Full access to all tables and views
- ✅ No additional API development needed
- ✅ Best performance for large datasets
- ✅ Supports incremental refresh

**Cons:**
- ⚠️ Requires VPN/secure connection to database
- ⚠️ Need to create read-only database user
- ⚠️ May need firewall rules for Power BI Service

**Use Case:** Best for production environments where Power BI Desktop/Service can connect directly to the MySQL server.

---

### Option 2: API Integration

**Pros:**
- ✅ More secure (no direct DB access)
- ✅ Can apply business logic before sending data
- ✅ Works across firewalls easily
- ✅ RESTful API can be versioned

**Cons:**
- ⚠️ Requires API development
- ⚠️ Additional authentication layer needed
- ⚠️ Slower than direct DB connection
- ⚠️ More complex to maintain

**Use Case:** When direct database access is not allowed due to security policies.

---

### Option 3: CSV Export Integration

**Pros:**
- ✅ Simple to implement
- ✅ No network/firewall concerns
- ✅ Good for historical snapshots

**Cons:**
- ⚠️ Manual refresh process
- ⚠️ Not real-time
- ⚠️ File management overhead
- ⚠️ Limited scalability

**Use Case:** For ad-hoc analysis or when other options are not available.

---

## 🗄️ Database Direct Connection (Recommended)

### Step 1: Create Read-Only Database User

Connect to MySQL as root and create a dedicated user for Power BI:

```sql
-- Create Power BI user with read-only access
CREATE USER 'powerbi_readonly'@'%' IDENTIFIED BY 'YourSecurePassword123!';

-- Grant SELECT privileges on cmmseng database
GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'%';

-- Apply privileges
FLUSH PRIVILEGES;

-- Verify user creation
SELECT user, host FROM mysql.user WHERE user = 'powerbi_readonly';
```

**Security Notes:**
- Use a strong password (min 16 characters, mixed case, numbers, symbols)
- Limit host to specific IP if possible: `'powerbi_readonly'@'YOUR_POWERBI_IP'`
- Never grant INSERT, UPDATE, DELETE permissions
- Regularly rotate passwords

### Step 2: Configure Firewall Rules

#### On VPS Server:
```bash
# Allow MySQL port from Power BI IP
sudo ufw allow from YOUR_POWERBI_IP to any port 3306

# Or if using specific interface
sudo iptables -A INPUT -p tcp --dport 3306 -s YOUR_POWERBI_IP -j ACCEPT
```

#### In MySQL Configuration:
Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
# Allow external connections
bind-address = 0.0.0.0

# Or bind to specific IP
# bind-address = YOUR_SERVER_IP
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### Step 3: Test Connection

From your Power BI machine, test the connection:

```bash
mysql -h YOUR_SERVER_IP -u powerbi_readonly -p cmmseng
```

If successful, you'll see:
```
Welcome to the MySQL monitor.
mysql>
```

### Step 4: Connect Power BI Desktop

1. **Open Power BI Desktop**

2. **Get Data** → **Database** → **MySQL database**

3. **Enter Connection Details:**
   - **Server:** `your-server-ip:3306` or `your-domain.com:3306`
   - **Database:** `cmmseng`
   - Click **OK**

4. **Authentication:**
   - Choose **Database**
   - **User name:** `powerbi_readonly`
   - **Password:** `YourSecurePassword123!`
   - Click **Connect**

5. **Navigator:**
   - Select tables you need (see [recommended tables](#recommended-tables-for-reporting))
   - Choose **Load** or **Transform Data** (for Power Query editing)

6. **Import Mode:**
   - Choose **Import** for scheduled refresh
   - Or **DirectQuery** for real-time data (slower performance)

---

## 📊 Database Schema for Reporting

### Recommended Tables for Reporting

#### 1. **Work Orders Analysis**

**Main Table:** `work_orders`
```sql
SELECT 
    wo.id,
    wo.wo_number,
    wo.operator_name,
    wo.shift,
    wo.problem_type,
    wo.priority,
    wo.status,
    wo.assign_to AS department,
    wo.mttr,
    wo.total_downtime,
    wo.created_at AS submitted_date,
    wo.reviewed_at,
    wo.approved_at,
    wo.started_at,
    wo.completed_at,
    wo.closed_at,
    -- Related tables
    a.name AS area_name,
    sa.name AS sub_area_name,
    ast.name AS asset_name,
    sast.name AS sub_asset_name,
    u.name AS created_by_name,
    wc.labour_cost,
    wc.parts_cost,
    wc.downtime_cost,
    wc.total_cost
FROM work_orders wo
LEFT JOIN areas a ON wo.area_id = a.id
LEFT JOIN sub_areas sa ON wo.sub_area_id = sa.id
LEFT JOIN assets ast ON wo.asset_id = ast.id
LEFT JOIN sub_assets sast ON wo.sub_asset_id = sast.id
LEFT JOIN users u ON wo.created_by_gpid = u.gpid
LEFT JOIN wo_costs wc ON wo.id = wc.work_order_id
WHERE wo.deleted_at IS NULL;
```

#### 2. **PM Compliance Tracking**

**Main Table:** `pm_executions`
```sql
SELECT 
    pe.id,
    ps.code AS pm_code,
    ps.title AS pm_title,
    ps.department,
    pe.scheduled_date,
    pe.actual_start,
    pe.actual_end,
    pe.duration,
    pe.status,
    pe.is_on_time,
    -- Equipment details
    a.name AS area_name,
    ast.name AS asset_name,
    -- Technician details
    u.name AS technician_name,
    u.gpid AS technician_gpid,
    -- Cost details
    pc.labour_cost,
    pc.parts_cost,
    pc.overhead_cost,
    pc.total_cost
FROM pm_executions pe
INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
LEFT JOIN areas a ON ps.area_id = a.id
LEFT JOIN assets ast ON ps.asset_id = ast.id
LEFT JOIN users u ON pe.executed_by_gpid = u.gpid
LEFT JOIN pm_costs pc ON pe.id = pc.pm_execution_id
WHERE pe.deleted_at IS NULL;
```

#### 3. **Inventory & Stock Levels**

**Main Table:** `parts`
```sql
SELECT 
    p.id,
    p.part_number,
    p.name,
    p.description,
    p.category,
    p.unit,
    p.current_stock,
    p.min_stock,
    p.unit_price,
    p.location,
    p.last_restocked_at,
    -- Stock status calculation
    CASE 
        WHEN p.current_stock = 0 THEN 'Out of Stock'
        WHEN p.current_stock <= p.min_stock THEN 'Low Stock'
        ELSE 'Sufficient'
    END AS stock_status,
    -- Stock value
    p.current_stock * p.unit_price AS stock_value
FROM parts p
WHERE p.deleted_at IS NULL;
```

#### 4. **Equipment Performance**

**Main Table:** `assets`
```sql
SELECT 
    a.id,
    a.name AS asset_name,
    a.code AS asset_code,
    a.model,
    a.serial_number,
    a.installation_date,
    ar.name AS area_name,
    sa.name AS sub_area_name,
    -- Count work orders
    COUNT(DISTINCT wo.id) AS total_wo,
    -- Count PM executions
    COUNT(DISTINCT pe.id) AS total_pm,
    -- Average MTTR
    AVG(wo.mttr) AS avg_mttr,
    -- Total downtime
    SUM(wo.total_downtime) AS total_downtime
FROM assets a
LEFT JOIN areas ar ON a.area_id = ar.id
LEFT JOIN sub_areas sa ON a.sub_area_id = sa.id
LEFT JOIN work_orders wo ON a.id = wo.asset_id AND wo.deleted_at IS NULL
LEFT JOIN pm_schedules ps ON a.id = ps.asset_id
LEFT JOIN pm_executions pe ON ps.id = pe.pm_schedule_id AND pe.deleted_at IS NULL
WHERE a.deleted_at IS NULL
GROUP BY a.id;
```

#### 5. **Cost Analysis**

**Work Order Costs:**
```sql
SELECT 
    wo.wo_number,
    wo.problem_type,
    wo.assign_to AS department,
    wo.completed_at,
    wc.labour_cost,
    wc.parts_cost,
    wc.downtime_cost,
    wc.total_cost,
    a.name AS asset_name
FROM wo_costs wc
INNER JOIN work_orders wo ON wc.work_order_id = wo.id
LEFT JOIN assets a ON wo.asset_id = a.id
WHERE wo.deleted_at IS NULL;
```

**PM Costs:**
```sql
SELECT 
    ps.code AS pm_code,
    ps.department,
    pe.actual_end AS completion_date,
    pc.labour_cost,
    pc.parts_cost,
    pc.overhead_cost,
    pc.total_cost,
    a.name AS asset_name
FROM pm_costs pc
INNER JOIN pm_executions pe ON pc.pm_execution_id = pe.id
INNER JOIN pm_schedules ps ON pe.pm_schedule_id = ps.id
LEFT JOIN assets a ON ps.asset_id = a.id
WHERE pe.deleted_at IS NULL;
```

#### 6. **Technician Performance**

```sql
SELECT 
    u.gpid,
    u.name AS technician_name,
    u.department,
    -- PM metrics
    COUNT(DISTINCT pe.id) AS total_pm,
    SUM(CASE WHEN pe.is_on_time = 1 THEN 1 ELSE 0 END) AS on_time_pm,
    ROUND(SUM(CASE WHEN pe.is_on_time = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT pe.id), 2) AS pm_compliance_pct,
    -- WO metrics
    COUNT(DISTINCT wo.id) AS total_wo,
    AVG(wo.mttr) AS avg_mttr
FROM users u
LEFT JOIN pm_executions pe ON u.gpid = pe.executed_by_gpid AND pe.deleted_at IS NULL
LEFT JOIN wo_processes wp ON u.gpid = wp.performed_by_gpid
LEFT JOIN work_orders wo ON wp.work_order_id = wo.id AND wo.deleted_at IS NULL
WHERE u.role = 'technician' AND u.is_active = 1
GROUP BY u.id;
```

---

## 🔐 Security Configuration

### Database User Restrictions

**Grant only SELECT on specific tables** (more secure):

```sql
-- Revoke all privileges first
REVOKE ALL PRIVILEGES ON cmmseng.* FROM 'powerbi_readonly'@'%';

-- Grant SELECT on specific tables only
GRANT SELECT ON cmmseng.work_orders TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_executions TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_schedules TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.parts TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.inventories TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.assets TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.areas TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.sub_areas TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.sub_assets TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.users TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.wo_costs TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.pm_costs TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.wo_processes TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.inventory_movements TO 'powerbi_readonly'@'%';
GRANT SELECT ON cmmseng.stock_alerts TO 'powerbi_readonly'@'%';

FLUSH PRIVILEGES;
```

### Network Security

**VPN Access (Recommended):**
- Use VPN to access database server
- Don't expose MySQL port (3306) publicly
- Use SSH tunnel for Power BI Desktop:
  ```bash
  ssh -L 3307:localhost:3306 user@your-server.com
  ```
  Then connect Power BI to `localhost:3307`

**IP Whitelisting:**
```sql
-- Limit access to specific IP addresses
DROP USER 'powerbi_readonly'@'%';
CREATE USER 'powerbi_readonly'@'YOUR_POWERBI_IP' IDENTIFIED BY 'YourSecurePassword123!';
GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'YOUR_POWERBI_IP';
FLUSH PRIVILEGES;
```

### Power BI Service Gateway

For **Power BI Service** (cloud):
1. Install **On-premises data gateway** on a server that can access your database
2. Configure gateway with connection credentials
3. Use gateway for scheduled refresh

---

## 📈 Power BI Data Model

### Recommended Table Relationships

```
DimAreas (areas)
    ├── area_id ──▶ DimSubAreas (sub_areas)
    │                  └── sub_area_id ──▶ DimAssets (assets)
    │                                         └── asset_id ──▶ DimSubAssets (sub_assets)
    │
    ├── area_id ──▶ FactWorkOrders (work_orders)
    └── area_id ──▶ FactPmExecutions (pm_executions via pm_schedules)

DimUsers (users)
    ├── gpid ──▶ FactWorkOrders.created_by_gpid
    └── gpid ──▶ FactPmExecutions.executed_by_gpid

DimParts (parts)
    ├── part_id ──▶ FactInventories (inventories)
    ├── part_id ──▶ FactWoPartsUsage (wo_parts_usage)
    └── part_id ──▶ FactPmPartsUsage (pm_parts_usage)

FactWorkOrders (work_orders)
    ├── id ──▶ FactWoCosts (wo_costs).work_order_id
    └── id ──▶ FactWoProcesses (wo_processes).work_order_id

FactPmExecutions (pm_executions)
    ├── id ──▶ FactPmCosts (pm_costs).pm_execution_id
    └── pm_schedule_id ──▶ DimPmSchedules (pm_schedules)

DimDate (custom date table)
    ├── Date ──▶ FactWorkOrders.created_at
    ├── Date ──▶ FactWorkOrders.completed_at
    └── Date ──▶ FactPmExecutions.actual_end
```

### Creating Date Table (DAX)

```dax
DimDate = 
ADDCOLUMNS(
    CALENDAR(DATE(2024, 1, 1), DATE(2030, 12, 31)),
    "Year", YEAR([Date]),
    "Quarter", "Q" & FORMAT([Date], "Q"),
    "Month", FORMAT([Date], "MMMM"),
    "MonthNum", MONTH([Date]),
    "Week", WEEKNUM([Date]),
    "WeekDay", FORMAT([Date], "dddd"),
    "WeekDayNum", WEEKDAY([Date]),
    "YearMonth", FORMAT([Date], "YYYY-MM"),
    "YearQuarter", FORMAT([Date], "YYYY") & " Q" & FORMAT([Date], "Q")
)
```

---

## 📐 Sample DAX Measures

### Work Order Metrics

```dax
// Total Work Orders
Total WO = COUNTROWS(work_orders)

// Open Work Orders
Open WO = 
CALCULATE(
    COUNTROWS(work_orders),
    work_orders[status] IN {"submitted", "reviewed", "approved", "in_progress", "on_hold"}
)

// Completed Work Orders
Completed WO = 
CALCULATE(
    COUNTROWS(work_orders),
    work_orders[status] = "completed"
)

// Average MTTR (Mean Time To Repair)
Avg MTTR = 
AVERAGE(work_orders[mttr])

// Total Downtime (hours)
Total Downtime Hours = 
SUM(work_orders[total_downtime]) / 60

// Completion Rate %
WO Completion Rate = 
DIVIDE(
    [Completed WO],
    [Total WO],
    0
) * 100

// Average Resolution Time (days)
Avg Resolution Time = 
AVERAGEX(
    FILTER(work_orders, NOT(ISBLANK(work_orders[completed_at]))),
    DATEDIFF(work_orders[created_at], work_orders[completed_at], DAY)
)
```

### PM Compliance Metrics

```dax
// Total PM Executed
Total PM = COUNTROWS(pm_executions)

// On-Time PM
On-Time PM = 
CALCULATE(
    COUNTROWS(pm_executions),
    pm_executions[is_on_time] = 1
)

// PM Compliance %
PM Compliance % = 
DIVIDE(
    [On-Time PM],
    [Total PM],
    0
) * 100

// Overdue PM
Overdue PM = 
CALCULATE(
    COUNTROWS(pm_executions),
    pm_executions[is_on_time] = 0
)
```

### Cost Metrics

```dax
// Total WO Cost
Total WO Cost = SUM(wo_costs[total_cost])

// Total PM Cost
Total PM Cost = SUM(pm_costs[total_cost])

// Total Maintenance Cost
Total Maintenance Cost = [Total WO Cost] + [Total PM Cost]

// Average WO Cost
Avg WO Cost = AVERAGE(wo_costs[total_cost])

// Average PM Cost
Avg PM Cost = AVERAGE(pm_costs[total_cost])

// Parts Cost
Total Parts Cost = 
SUM(wo_costs[parts_cost]) + SUM(pm_costs[parts_cost])

// Labour Cost
Total Labour Cost = 
SUM(wo_costs[labour_cost]) + SUM(pm_costs[labour_cost])

// Cost by Department
Cost by Department = 
CALCULATE(
    [Total WO Cost],
    ALLEXCEPT(work_orders, work_orders[assign_to])
)
```

### Inventory Metrics

```dax
// Total Stock Value
Total Stock Value = 
SUMX(
    parts,
    parts[current_stock] * parts[unit_price]
)

// Low Stock Items
Low Stock Items = 
CALCULATE(
    COUNTROWS(parts),
    parts[current_stock] <= parts[min_stock]
)

// Out of Stock Items
Out of Stock Items = 
CALCULATE(
    COUNTROWS(parts),
    parts[current_stock] = 0
)

// Stock Availability %
Stock Availability % = 
DIVIDE(
    CALCULATE(COUNTROWS(parts), parts[current_stock] > 0),
    COUNTROWS(parts),
    0
) * 100
```

### Equipment Performance

```dax
// Equipment Availability %
Equipment Availability % = 
VAR TotalHours = 24 * 30 // 30 days
VAR DowntimeHours = SUM(work_orders[total_downtime]) / 60
RETURN
DIVIDE(TotalHours - DowntimeHours, TotalHours, 1) * 100

// MTBF (Mean Time Between Failures)
MTBF = 
VAR TotalOperatingHours = 24 * 30 * DISTINCTCOUNT(assets[id])
VAR TotalFailures = COUNTROWS(work_orders)
RETURN
DIVIDE(TotalOperatingHours, TotalFailures, 0)
```

---

## 🔄 Publishing & Refresh

### Publishing to Power BI Service

1. **Save your .pbix file** in Power BI Desktop

2. **Click Publish** → Select workspace

3. **Configure Gateway:**
   - Install On-premises data gateway on a server
   - Register gateway in Power BI Service
   - Configure data source credentials

4. **Schedule Refresh:**
   - Go to workspace → Dataset settings
   - Gateway connection → Select your gateway
   - Scheduled refresh → Enable
   - Set refresh frequency (e.g., daily at 6 AM)
   - Add time zone: Asia/Jakarta

### Recommended Refresh Schedule

**For Real-time Dashboards:**
- Refresh every 1 hour during business hours (8 AM - 6 PM)
- Refresh every 4 hours during off-hours

**For Daily Reports:**
- Refresh once daily at 6 AM (before work starts)

**For Weekly/Monthly Reports:**
- Refresh every Monday at 7 AM for weekly
- Refresh on 1st of month at 7 AM for monthly

### Incremental Refresh (for large datasets)

Configure incremental refresh to load only new/changed data:

1. **Power Query:**
   - Add parameters: `RangeStart` and `RangeEnd`
   - Filter date column:
     ```
     = Table.SelectRows(Source, each [created_at] >= RangeStart and [created_at] < RangeEnd)
     ```

2. **Incremental Refresh Policy:**
   - Archive data: 2 years
   - Refresh data: Last 7 days
   - Detect data changes: Yes

---

## 🛠️ Troubleshooting

### Issue 1: Cannot Connect to MySQL from Power BI

**Symptoms:** Connection timeout or "Cannot connect to server"

**Solutions:**
1. Check firewall rules allow port 3306
2. Verify MySQL bind-address is not 127.0.0.1
3. Test connection with `mysql -h SERVER -u powerbi_readonly -p`
4. Check VPN is connected (if using VPN)
5. Verify user has correct host permissions: `SELECT user, host FROM mysql.user;`

### Issue 2: MySQL Connector Not Found in Power BI

**Solution:**
1. Download MySQL/MariaDB connector from Power BI
2. Or install MySQL ODBC driver: https://dev.mysql.com/downloads/connector/odbc/
3. Restart Power BI Desktop

### Issue 3: Slow Query Performance

**Solutions:**
1. Add indexes to date columns used in filters
2. Use Import mode instead of DirectQuery
3. Filter data in Power Query (e.g., last 12 months only)
4. Create database views with pre-aggregated data

### Issue 4: Refresh Fails in Power BI Service

**Solutions:**
1. Verify gateway is online: Power BI Service → Settings → Manage gateways
2. Check gateway can connect to database
3. Update credentials in dataset settings
4. Check gateway logs: `C:\Program Files\On-premises data gateway\Logs`

### Issue 5: Data Not Updating After Refresh

**Solutions:**
1. Clear cache: Power BI Desktop → Options → Clear cache
2. Check scheduled refresh succeeded in Power BI Service
3. Verify database has new data: Run query directly in MySQL
4. Refresh metadata in Power Query

---

## 📚 Additional Resources

### Official Documentation
- [Power BI MySQL Connector](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-connect-mysql)
- [On-premises Data Gateway](https://docs.microsoft.com/en-us/data-integration/gateway/)
- [Incremental Refresh](https://docs.microsoft.com/en-us/power-bi/connect-data/incremental-refresh-overview)

### Best Practices
- Use Import mode for better performance (unless real-time needed)
- Create date tables for time intelligence
- Use measures instead of calculated columns
- Optimize data model with star schema
- Document all DAX measures
- Set up row-level security if needed

### Sample Dashboard Templates
Location: `resources/powerbi/templates/`
- `CMMS_Work_Orders_Dashboard.pbix`
- `CMMS_PM_Compliance_Dashboard.pbix`
- `CMMS_Cost_Analysis_Dashboard.pbix`
- `CMMS_Inventory_Dashboard.pbix`

---

## ✅ Phase 17 Integration Checklist

- [x] Power BI integration guide created
- [ ] Read-only database user created
- [ ] Database views for reporting created (optional)
- [ ] Firewall rules configured
- [ ] Power BI Desktop connection tested
- [ ] Sample .pbix file created
- [ ] Gateway installed and configured
- [ ] Scheduled refresh configured
- [ ] Dashboard published to Power BI Service
- [ ] User training on Power BI dashboards

---

**Last Updated:** November 26, 2025  
**Document Owner:** Nandang Wijaya  
**Contact:** [Your Email]

---

**🎉 Power BI integration ready! Start creating your dashboards!**
