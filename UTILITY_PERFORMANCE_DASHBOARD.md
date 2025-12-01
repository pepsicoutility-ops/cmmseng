# Utility Performance Dashboard

## Overview
The Utility Performance Dashboard provides comprehensive performance analysis, energy monitoring, and maintenance tracking for all utility department equipment.

## Features

### 1. Performance Metrics (UtilityPerformanceWidget)
Real-time statistics showing:
- **Checklists Today**: Daily checklist completion across all equipment
  - Breakdown by equipment: Chiller 1, Chiller 2, Compressor 1, Compressor 2, AHU
  - Color-coded status (Green: ≥10 checks, Yellow: <10 checks)
  
- **This Month Total**: Cumulative checklist count for the current month
  
- **Daily Compliance**: Percentage of completed vs expected checklists
  - Target: 15 checklists per day (5 equipment × 3 checks)
  - Color-coded: Green (≥80%), Yellow (60-79%), Red (<60%)
  
- **Average Chiller Temperature**: Combined average suction temperature
  - Monitored for both Chiller 1 and Chiller 2
  - Helps identify cooling efficiency

### 2. Energy Performance Chart (EnergyMetricsChartWidget)
Line chart displaying 7-day trends:
- **Chiller 1 Temperature** (Blue line)
- **Chiller 2 Temperature** (Purple line)
- **Compressor 1 Oil Pressure** (Green line)
- **Compressor 2 Oil Pressure** (Yellow line)

Use this chart to:
- Identify abnormal temperature/pressure patterns
- Track energy efficiency trends
- Predict maintenance needs
- Compare equipment performance

### 3. Master Checklists Table (MasterChecklistsWidget)
Comprehensive table showing all checklists from the last 7 days:

**Columns:**
- **ID**: Unique identifier (C1-, C2-, CP1-, CP2-, AHU- prefix)
- **Equipment**: Equipment type with color-coded badges
- **Date/Time**: When the checklist was completed
- **Shift**: Work shift (Morning/Afternoon/Night)
- **Suction Temp**: Suction temperature reading (°C)
- **Discharge Temp**: Discharge temperature reading (°C)
- **Oil Pressure**: Oil pressure reading (Bar)
- **Status**: Equipment status (Normal/Warning/Critical)
- **Created By**: GPID of technician who completed the checklist

**Features:**
- Sortable by any column
- Searchable by ID and Equipment
- Auto-refreshes every 30 seconds
- Pagination: 10, 25, or 50 entries per page

## Access Control

### Who Can Access:
1. **Super Admin** - Full access
2. **Manager** - Full access
3. **Assistant Manager** - Full access
4. **Utility Department Technicians** - Full access to Utility Performance Dashboard
5. **Other Departments** - No access

### Navigation:
- Located in **"Utility Performance"** navigation group
- Menu item: **"Performance Dashboard"**
- Icon: Chart bar square (📊)

## Equipment Monitored

### Chiller 1
- Model: Chiller1Checklist
- Key Metrics: Suction temperature, Discharge temperature, Oil pressure
- Expected Checks: 3 per day

### Chiller 2
- Model: Chiller2Checklist
- Key Metrics: Suction temperature, Discharge temperature, Oil pressure
- Expected Checks: 3 per day

### Compressor 1
- Model: Compressor1Checklist
- Key Metrics: Oil pressure
- Expected Checks: 3 per day

### Compressor 2
- Model: Compressor2Checklist
- Key Metrics: Oil pressure
- Expected Checks: 3 per day

### AHU (Air Handling Unit)
- Model: AhuChecklist
- Key Metrics: Status monitoring
- Expected Checks: 3 per day

## Usage Guide

### For Managers/Supervisors:
1. **Monitor Daily Compliance**
   - Check the "Daily Compliance" stat each morning
   - Target: Above 80% completion rate
   - Investigate if compliance drops below 60%

2. **Review Energy Trends**
   - Check the 7-day chart for anomalies
   - Look for sudden temperature spikes or pressure drops
   - Compare week-over-week patterns

3. **Verify Checklist Completion**
   - Review the Master Checklists table
   - Ensure all shifts are performing checks
   - Check for missing equipment checks

### For Technicians:
1. **Track Personal Performance**
   - Filter Master Checklists by your GPID
   - Review your completion rate
   - Identify missed checks

2. **Monitor Equipment Health**
   - Check temperature/pressure readings
   - Report abnormal values immediately
   - Compare with historical data

### For Analysts:
1. **Performance Analysis**
   - Export data from Master Checklists table
   - Calculate average completion rates
   - Identify peak/low performance periods

2. **Energy Efficiency**
   - Track energy consumption trends
   - Identify equipment inefficiencies
   - Recommend optimization strategies

## Alerts & Notifications

### Status Color Codes:
- **Green (Success)**: Normal operation
- **Yellow (Warning)**: Attention needed
- **Red (Danger)**: Critical issue
- **Gray**: No data or inactive

### Compliance Thresholds:
- **≥80%**: Excellent (Green)
- **60-79%**: Needs Improvement (Yellow)
- **<60%**: Critical (Red)

## Technical Details

### Database Tables:
- `chiller1_checklists`
- `chiller2_checklists`
- `compressor1_checklists`
- `compressor2_checklists`
- `ahu_checklists`

### Models:
- `App\Models\Chiller1Checklist`
- `App\Models\Chiller2Checklist`
- `App\Models\Compressor1Checklist`
- `App\Models\Compressor2Checklist`
- `App\Models\AhuChecklist`

### Widgets:
- `App\Filament\Widgets\UtilityPerformanceWidget`
- `App\Filament\Widgets\EnergyMetricsChartWidget`
- `App\Filament\Widgets\MasterChecklistsWidget`

### Page:
- `App\Filament\Pages\UtilityPerformanceAnalysis`

## Future Enhancements

Planned features:
- [ ] PDF export of daily/weekly reports
- [ ] Email alerts for critical status
- [ ] WhatsApp notifications for missed checks
- [ ] Predictive maintenance recommendations
- [ ] Energy cost calculations
- [ ] Equipment comparison reports
- [ ] Mobile app integration
- [ ] Real-time sensor integration

## Troubleshooting

### No data showing:
- Verify checklists are being created
- Check date filters (7-day window)
- Confirm user has proper access rights

### Incorrect calculations:
- Verify database has valid data
- Check for NULL values in temperature/pressure fields
- Review expected daily target (15 checks)

### Access denied:
- Confirm user department is "Utility"
- Verify user role is manager/asisten_manager or higher
- Check PanelProvider access control

## Support

For issues or feature requests, contact:
- Engineering Manager
- System Administrator
- IT Support Team
