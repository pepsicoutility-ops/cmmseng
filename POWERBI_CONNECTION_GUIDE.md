# Power BI Connection Guide

## ✅ Database Setup Complete

The Power BI integration has been successfully configured with:
- **Database User**: `powerbi_readonly` (read-only access)
- **6 Optimized Views**: Ready for reporting
- **Security**: SELECT permissions only on `cmmseng` database

---

## 📊 Connection Details

Use these credentials to connect Power BI Desktop to your CMMS database:

```
Server: localhost (or your Laragon MySQL host)
Port: 3306
Database: cmmseng
Username: powerbi_readonly
Password: PowerBI@2025
```

---

## 📋 Available Views & Data

| View Name | Records | Description |
|-----------|---------|-------------|
| `vw_powerbi_work_orders` | 6 | Work order analysis with downtime, MTTR, status tracking |
| `vw_powerbi_pm_compliance` | 5 | PM execution tracking, on-time compliance, technician performance |
| `vw_powerbi_inventory` | 14 | Stock levels, valuation, low-stock alerts |
| `vw_powerbi_equipment` | 5 | Asset performance, WO frequency, PM metrics |
| `vw_powerbi_costs` | 8 | Unified cost analysis (WO + PM), departmental spending |
| `vw_powerbi_technician_performance` | 24 | Technician KPIs, PM compliance rates |

---

## 🔌 How to Connect in Power BI Desktop

### Method 1: MySQL Connector (Recommended)

1. **Open Power BI Desktop**

2. **Get Data** → **More...** → Search for "MySQL"

3. **Enter Connection Details**:
   - Server: `localhost`
   - Database: `cmmseng`

4. **Authentication**:
   - Choose "Database" authentication
   - Username: `powerbi_readonly`
   - Password: `PowerBI@2025`

5. **Select Tables**:
   - Check all 6 `vw_powerbi_*` views
   - Click "Load" or "Transform Data"

### Method 2: ODBC Connector (Alternative)

1. **Install MySQL ODBC Driver** (if not already installed):
   - Download from: https://dev.mysql.com/downloads/connector/odbc/

2. **Power BI Desktop**:
   - Get Data → ODBC
   - Enter connection string:
     ```
     DRIVER={MySQL ODBC 8.0 Unicode Driver};
     SERVER=localhost;
     DATABASE=cmmseng;
     UID=powerbi_readonly;
     PWD=PowerBI@2025;
     ```

---

## 📈 Quick Start Queries

### Test Connection (Sample Queries)

```sql
-- Work Order Summary
SELECT 
    department, 
    COUNT(*) as total_wo,
    AVG(mttr_hours) as avg_mttr
FROM vw_powerbi_work_orders
GROUP BY department;

-- PM Compliance Rate
SELECT 
    department,
    SUM(is_on_time) * 100.0 / COUNT(*) as compliance_rate
FROM vw_powerbi_pm_compliance
GROUP BY department;

-- Low Stock Items
SELECT 
    part_name,
    current_stock,
    min_stock,
    stock_status
FROM vw_powerbi_inventory
WHERE stock_status IN ('Low Stock', 'Out of Stock');
```

---

## 📊 Pre-Built DAX Measures

Copy these measures into Power BI for instant analytics:

### Work Order Metrics
```dax
Total Work Orders = COUNTROWS(vw_powerbi_work_orders)
Avg MTTR (Hours) = AVERAGE(vw_powerbi_work_orders[mttr_hours])
Completion Rate % = DIVIDE([Completed WO], [Total Work Orders]) * 100
Open WO Count = SUMX(vw_powerbi_work_orders, [is_open])
```

### PM Compliance
```dax
PM Compliance % = 
    DIVIDE(
        SUMX(vw_powerbi_pm_compliance, [is_on_time]),
        COUNTROWS(vw_powerbi_pm_compliance)
    ) * 100

On-Time PM = SUMX(vw_powerbi_pm_compliance, [is_on_time])
Late PM = SUMX(vw_powerbi_pm_compliance, 1 - [is_on_time])
```

### Cost Analysis
```dax
Total Costs = SUM(vw_powerbi_costs[total_cost])
Labour Costs = SUM(vw_powerbi_costs[labour_cost])
Parts Costs = SUM(vw_powerbi_costs[parts_cost])
Cost Per WO = DIVIDE([Total Costs], [Total Work Orders])
```

### Inventory Health
```dax
Stock Value = SUM(vw_powerbi_inventory[stock_value])
Low Stock Count = COUNTROWS(FILTER(vw_powerbi_inventory, [stock_status] = "Low Stock"))
Out of Stock = COUNTROWS(FILTER(vw_powerbi_inventory, [stock_status] = "Out of Stock"))
```

---

## 🎨 Recommended Visualizations

### Dashboard 1: Work Order Analytics
- **KPI Cards**: Total WO, Avg MTTR, Completion Rate
- **Line Chart**: WO trend by month (`year_month`)
- **Bar Chart**: WO by department
- **Donut Chart**: WO by priority
- **Table**: Top 10 assets by WO count

### Dashboard 2: PM Compliance
- **Gauge Chart**: PM Compliance % (target: 95%)
- **Column Chart**: On-Time vs Late PM by month
- **Matrix**: Compliance by department × technician
- **Line Chart**: PM trend over time

### Dashboard 3: Cost Analysis
- **KPI Cards**: Total Costs, Labour %, Parts %
- **Waterfall Chart**: Cost breakdown by type
- **Area Chart**: Cost trends by month
- **Tree Map**: Costs by department

### Dashboard 4: Inventory Management
- **KPI Cards**: Stock Value, Low Stock Items
- **Table**: Critical stock alerts (filtered by status)
- **Bar Chart**: Stock value by category
- **Scatter Plot**: Current stock vs Min stock

---

## 🔐 Security Notes

✅ **Read-Only Access**: The `powerbi_readonly` user has SELECT permissions only
✅ **Database Scoped**: Access limited to `cmmseng` database only
✅ **Production Safe**: No write/update/delete capabilities

⚠️ **Password Management**: 
- Change default password if deploying to production
- Store credentials securely (Azure Key Vault, credential manager)

---

## 🔄 Data Refresh

### Power BI Service (Cloud)
1. Publish report to Power BI Service
2. Configure Gateway connection (if using on-premise MySQL)
3. Set scheduled refresh (e.g., daily at 6 AM)

### Power BI Desktop
- Click "Refresh" button to reload latest data
- Views automatically reflect current database state

---

## 📚 Additional Resources

- **Full Integration Guide**: `POWERBI_INTEGRATION.md`
- **Architecture Documentation**: `ARCHITECTURE.md`
- **Database Views Source**: `database/powerbi_views.sql`
- **Migration File**: `database/migrations/2025_11_26_204358_create_powerbi_user_and_views.php`

---

## ✅ Verification Checklist

- [x] Database user `powerbi_readonly` created
- [x] Read-only SELECT permissions granted
- [x] 6 Power BI views created successfully
- [x] All views returning data (tested)
- [ ] Power BI Desktop connected successfully
- [ ] Sample dashboard created
- [ ] Data refresh tested

---

## 🆘 Troubleshooting

### Connection Failed
- Verify MySQL service is running in Laragon
- Check firewall allows port 3306
- Confirm credentials: `powerbi_readonly` / `PowerBI@2025`

### No Data in Views
- Run test queries in MySQL Workbench
- Check source tables have data
- Verify filters in views (e.g., `deleted_at IS NULL`)

### Slow Performance
- Views are pre-optimized with indexes
- Consider adding date range filters in Power BI
- Limit historical data if needed (e.g., last 12 months)

---

**🎉 Setup Complete! You're ready to build insightful dashboards.**

For questions or issues, refer to `POWERBI_INTEGRATION.md` for detailed documentation.
