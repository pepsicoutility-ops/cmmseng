# 🎯 Performance Development - KPI Gap Analysis & Feature Roadmap

**Document Created:** December 24, 2025  
**Analysis For:** Engineering Department KPIs 2026  
**Current System:** CMMS v1.0 (30 phases completed)  
**Developer:** Nandang Wijaya

---

## 📋 Executive Summary

This document analyzes the gap between current CMMS capabilities and the 2026 Engineering Performance Development KPIs. The analysis covers:
- ✅ **Currently Supported KPIs** (available features)
- ⚠️ **Partially Supported KPIs** (need enhancements)
- ❌ **Missing KPIs** (new features required)

**Overall Status:**
- **Supported:** 4 KPIs (26%)
- **Partially Supported:** 3 KPIs (20%)
- **Missing:** 8 KPIs (54%)
- **Total KPIs:** 15 metrics

---

## ✅ Currently Supported KPIs (4 Metrics)

### 1. PM Compliance: >90% ✅
**Status:** FULLY SUPPORTED  
**Current Features:**
- ✅ PM Schedule system with frequency-based filtering
- ✅ PM Execution tracking with on-time/late status
- ✅ Compliance percentage calculation
- ✅ Weekly/monthly compliance reports
- ✅ ComplianceService with scheduled tasks
- ✅ Power BI view: `vw_powerbi_pm_compliance`

**How to Track:**
1. Navigate to Reports → PM Compliance
2. Filter by date range (weekly/monthly)
3. View compliance percentage per equipment/department
4. Export to Excel/PDF or view in Power BI

**Database Tables:**
- `pm_compliances` (period, compliance_percentage, on_time_count, late_count)
- `pm_executions` (scheduled_date, actual_start, actual_end, is_on_time)

---

### 2. Downtime: <1.5% Technical Downtime ✅
**Status:** FULLY SUPPORTED  
**Current Features:**
- ✅ Work Order system with downtime tracking
- ✅ Automatic downtime calculation (actual_start - actual_end)
- ✅ Downtime categorization (planned/unplanned)
- ✅ Downtime percentage calculation per equipment
- ✅ Power BI view: `vw_powerbi_work_orders` (downtime_minutes column)

**How to Track:**
1. Navigate to Reports → Work Order Reports
2. Filter by downtime > 0 minutes
3. Calculate: (Total Downtime / Total Production Time) × 100
4. Group by department/equipment/period

**Database Tables:**
- `work_orders` (downtime_minutes, downtime_cost)
- `equipment_troubles` (downtime_duration_minutes)

**Calculation Formula:**
```php
$downtimePercentage = ($totalDowntimeMinutes / $totalProductionMinutes) * 100;
// Target: < 1.5%
```

---

### 3. MTTR: <50 Minutes (Average) ✅
**Status:** FULLY SUPPORTED  
**Current Features:**
- ✅ Work Order system with MTTR auto-calculation
- ✅ MTTR = actual_end - actual_start (breakdown to completed)
- ✅ Average MTTR per equipment/department/period
- ✅ Power BI view with MTTR metrics
- ✅ Dashboard widget: WO Status (shows avg MTTR)

**How to Track:**
1. Navigate to Reports → Work Order Reports
2. Filter by status = 'completed'
3. View MTTR column (minutes)
4. Calculate average per period: AVG(MTTR)

**Database Tables:**
- `work_orders` (actual_start, actual_end, status)
- Auto-calculated: MTTR = TIMESTAMPDIFF(MINUTE, actual_start, actual_end)

**Calculation Formula:**
```php
$mttr = $workOrder->actual_end->diffInMinutes($workOrder->actual_start);
// Target: < 50 minutes average
```

---

### 4. Technician Performance Assessment ✅
**Status:** FULLY SUPPORTED  
**Current Features:**
- ✅ Technician Performance resource (Phase 13.5)
- ✅ Scoring system: WO completed, PM completed, downtime, rework count
- ✅ Performance percentage calculation
- ✅ Manager/AM access only
- ✅ Filtering by technician, department, date range

**How to Track:**
1. Navigate to Reports → Technician Performance
2. Select period (monthly recommended)
3. View performance score (0-100%)
4. Filter by department/technician

**Database Tables:**
- `technician_performances` (wo_completed, pm_completed, downtime_minutes, rework_count, performance_score)

---

## ⚠️ Partially Supported KPIs (3 Metrics)

### 5. Condition Based Monitoring (CBM): >90% Compliance ⚠️
**Status:** PARTIALLY SUPPORTED  
**Current Capabilities:**
- ✅ 5 Utility checklists with equipment parameter monitoring
  - Compressor 1 & 2 (14 parameters: temperature, pressure, oil, refrigerant)
  - Chiller 1 & 2 (29 parameters: evaporator, condenser, motor, cooling)
  - AHU (43 parameters: filters, pressure drops)
- ✅ Historical data storage for trend analysis
- ✅ Shift-based monitoring (3 shifts per day)
- ❌ No CBM compliance percentage tracking
- ❌ No alert thresholds for parameter deviation
- ❌ No automatic scoring system for CBM execution

**Missing Features:**
1. **CBM Schedule & Compliance Tracking:**
   - Define CBM schedules per equipment (e.g., daily, shift-based)
   - Track CBM execution vs schedule
   - Calculate compliance percentage: (Executed / Scheduled) × 100

2. **Parameter Deviation Alerts:**
   - Define normal ranges for each parameter (min/max thresholds)
   - Trigger alerts when parameters exceed thresholds
   - Notify technicians/managers via Telegram/WhatsApp

3. **CBM Dashboard Widget:**
   - Display CBM compliance percentage
   - Show equipment with missed CBM checks
   - List parameters with deviations

**Recommended Implementation:**
- Add `cbm_schedules` table (equipment_id, checklist_type, frequency, last_execution)
- Add `cbm_parameter_thresholds` table (equipment_id, parameter_name, min_value, max_value)
- Create `CbmComplianceService` to calculate compliance
- Add CBM compliance widget to dashboard

---

### 6. Utility Cost Tracking: Water/Electricity/Gas per kg ⚠️
**Status:** PARTIALLY SUPPORTED  
**Current Capabilities:**
- ✅ Work Order system tracks labor cost (hourly rates)
- ✅ PM cost tracking (parts + labor)
- ✅ Power BI view: `vw_powerbi_costs`
- ❌ No production weight tracking (kg produced)
- ❌ No utility consumption integration (water/electricity/gas meters)
- ❌ No per-kg cost calculation

**Current Cost Formula:**
```
Cost = (Labor Hours × Hourly Rate) + Parts Cost
```

**Missing Features:**
1. **Production Data Integration:**
   - Track daily/shift production weight (kg)
   - Link production data to equipment/area
   - Store in `production_records` table

2. **Utility Consumption Tracking:**
   - Water consumption (liters per shift/day)
   - Electricity consumption (kWh per shift/day)
   - Gas consumption (kWh per shift/day)
   - Store in `utility_consumptions` table

3. **Per-kg Cost Calculation:**
   - Water per kg = Total Water (L) / Production Weight (kg)
   - Electricity per kg = Total Electricity (kWh) / Production Weight (kg)
   - Gas per kg = Total Gas (kWh) / Production Weight (kg)

4. **Cost Tracking Dashboard:**
   - Display daily/weekly/monthly per-kg costs
   - Compare against targets (Water: <11.07 L/kg, Electricity: <0.96 kWh/kg, Gas: +2.06 kWh/kg)
   - Alert when exceeding thresholds

**Recommended Implementation:**
- Add `production_records` table (date, shift, area_id, weight_kg, created_at)
- Add `utility_consumptions` table (date, shift, area_id, water_liters, electricity_kwh, gas_kwh)
- Create `UtilityCostService` with per-kg calculation
- Add utility cost dashboard widget with trend charts

---

### 7. RCA Compliance for Downtime >10 Minutes: >90% Completion ⚠️
**Status:** PARTIALLY SUPPORTED  
**Current Capabilities:**
- ✅ Work Order system tracks downtime minutes
- ✅ Equipment Trouble tracking (Phase 28)
- ✅ AI-powered Root Cause Analysis (RCA) service (Phase 21)
  - Pattern detection (frequency > 3 occurrences)
  - Root cause scoring with confidence levels
  - Historical analysis (recurring problems)
- ❌ No mandatory RCA requirement for downtime >10 minutes
- ❌ No RCA completion tracking/compliance percentage
- ❌ No automatic trigger for RCA creation

**Missing Features:**
1. **RCA Requirement System:**
   - Auto-flag Work Orders with downtime >10 minutes as "RCA Required"
   - Prevent WO closure until RCA is completed
   - Track RCA status (pending/in_progress/completed)

2. **RCA Completion Tracking:**
   - Store RCA data: root cause, corrective actions, preventive measures
   - Calculate compliance: (RCA Completed / RCA Required) × 100
   - Target: >90% completion rate

3. **RCA Dashboard:**
   - Display RCA compliance percentage
   - List pending RCAs (overdue alerts)
   - Show recurring root causes (top 5)

4. **RCA Integration with AI Service:**
   - Auto-generate RCA suggestions using existing AIAnalyticsService
   - Pre-fill root cause analysis based on historical data
   - Provide corrective action recommendations

**Recommended Implementation:**
- Add `rca_required` boolean field to `work_orders` table
- Add `rca_status` enum field (pending/completed) to `work_orders`
- Add `root_cause_analyses` table (wo_id, root_cause, corrective_actions, preventive_measures)
- Update WorkOrder workflow to enforce RCA before closure
- Create RCA compliance widget and report

---

## ❌ Missing KPIs (8 Metrics - New Features Required)

### 8. Kaizen Tracking: Min. 4 per Year (Per Person) ❌
**Status:** NOT SUPPORTED  
**Target:** Minimum 4 improvement ideas per person per year  
**Scoring:** RECON = 5, DT reduction = 3, Safety & Quality = 1

**Required Features:**
1. **Kaizen Management System:**
   - Kaizen submission form (title, description, category, before/after)
   - Categories: RECON, DT Reduction, Safety & Quality
   - Score calculation based on category
   - Attachment support (photos, documents)

2. **Kaizen Workflow:**
   - Status: Submitted → Under Review → Approved/Rejected → Implemented
   - Approval workflow (manager/AM)
   - Implementation tracking (who, when, cost)
   - Impact measurement (cost saved, downtime reduced)

3. **Kaizen Dashboard:**
   - Display total Kaizens per person
   - Show Kaizen status distribution
   - Calculate total score per person
   - Alert when below target (< 4 per year)

4. **Kaizen Reports:**
   - Monthly/yearly Kaizen summary per department
   - Top contributors (highest score/count)
   - Category breakdown (RECON/DT/Safety)
   - Export to Excel/PDF

**Database Schema:**
```sql
CREATE TABLE kaizens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submitted_by_gpid VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('RECON', 'DT_REDUCTION', 'SAFETY_QUALITY') NOT NULL,
    score INT NOT NULL, -- RECON=5, DT=3, Safety=1
    status ENUM('submitted', 'under_review', 'approved', 'rejected', 'implemented') DEFAULT 'submitted',
    before_situation TEXT,
    after_situation TEXT,
    cost_saved DECIMAL(10,2),
    implementation_date DATE,
    attachments JSON, -- [{name, path}]
    reviewed_by_gpid VARCHAR(255),
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by_gpid) REFERENCES users(gpid),
    FOREIGN KEY (reviewed_by_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- All users: Submit Kaizens
- Managers/AM: Review and approve
- Super Admin: Full access

---

### 9. Abnormality Finding & Fixing: Min. 5 per Month (Per Person) ❌
**Status:** NOT SUPPORTED  
**Target:** Minimum 5 abnormalities found and fixed per person per month

**Required Features:**
1. **Abnormality Tracking System:**
   - Abnormality report form (location, description, severity, photo)
   - Severity levels: Critical, High, Medium, Low
   - Link to equipment/area
   - Finding date and finder GPID

2. **Fixing Workflow:**
   - Status: Found → Assigned → In Progress → Fixed → Verified
   - Auto-assign to area owner or technician
   - Fix deadline calculation based on severity
   - Verification by manager/AM

3. **Abnormality Dashboard:**
   - Display total findings per person
   - Show fix rate percentage
   - List overdue abnormalities
   - Alert when below target (< 5 per month)

4. **Abnormality Reports:**
   - Monthly summary per person/department
   - Fix rate: (Fixed / Total Found) × 100
   - Average fix time per severity
   - Recurring abnormalities (top 5)

**Database Schema:**
```sql
CREATE TABLE abnormalities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    found_by_gpid VARCHAR(255) NOT NULL,
    area_id BIGINT UNSIGNED,
    asset_id BIGINT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    status ENUM('found', 'assigned', 'in_progress', 'fixed', 'verified') DEFAULT 'found',
    finding_date DATE NOT NULL,
    assigned_to_gpid VARCHAR(255),
    fix_deadline DATE,
    fixed_date DATE,
    verified_by_gpid VARCHAR(255),
    fix_notes TEXT,
    photos JSON, -- [{name, path}]
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (found_by_gpid) REFERENCES users(gpid),
    FOREIGN KEY (assigned_to_gpid) REFERENCES users(gpid),
    FOREIGN KEY (verified_by_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- All users: Report abnormalities
- Technicians: Fix assigned abnormalities
- Managers/AM: Verify fixes

---

### 10. OPL & SOP Generation: Min. 2 per Month (Per Person) ❌
**Status:** NOT SUPPORTED  
**Target:** Minimum 2 OPL (One Point Lesson) or SOP (Standard Operating Procedure) per person per month

**Required Features:**
1. **Document Management System:**
   - OPL/SOP creation form (title, content, category, version)
   - Categories: Safety, Quality, Maintenance, Operation
   - Rich text editor with image support
   - Version control (v1.0, v1.1, etc.)
   - PDF export with PepsiCo branding

2. **Document Workflow:**
   - Status: Draft → Review → Approved → Published
   - Approval workflow (manager/AM)
   - Distribution to relevant departments
   - Read acknowledgment tracking

3. **Document Library:**
   - Searchable repository (by title, category, keyword)
   - Filter by department, equipment, category
   - Download count tracking
   - Latest documents feed

4. **Document Dashboard:**
   - Display total documents per person
   - Show approval rate
   - List pending approvals
   - Alert when below target (< 2 per month)

**Database Schema:**
```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    created_by_gpid VARCHAR(255) NOT NULL,
    type ENUM('OPL', 'SOP') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('safety', 'quality', 'maintenance', 'operation') NOT NULL,
    version VARCHAR(10) DEFAULT 'v1.0',
    status ENUM('draft', 'review', 'approved', 'published') DEFAULT 'draft',
    reviewed_by_gpid VARCHAR(255),
    review_date DATE,
    approval_notes TEXT,
    department VARCHAR(255),
    related_equipment_id BIGINT UNSIGNED,
    pdf_path VARCHAR(500),
    download_count INT DEFAULT 0,
    published_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_gpid) REFERENCES users(gpid),
    FOREIGN KEY (reviewed_by_gpid) REFERENCES users(gpid)
);

CREATE TABLE document_acknowledgments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    user_gpid VARCHAR(255) NOT NULL,
    acknowledged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- All users: Create OPL/SOP, view published documents
- Managers/AM: Review and approve
- Super Admin: Full access

---

### 11. Safety %LTI: Must be 0 (Lost Time Injury) ❌
**Status:** NOT SUPPORTED  
**Target:** 0% LTI (Zero lost time injuries)

**Required Features:**
1. **Safety Incident Tracking:**
   - Incident report form (date, location, description, severity)
   - Incident types: LTI (Lost Time Injury), First Aid, Near Miss
   - Injury classification (OSHA standards)
   - Days lost due to injury
   - Medical treatment tracking

2. **Investigation Workflow:**
   - Status: Reported → Under Investigation → Root Cause Identified → Corrective Actions → Closed
   - Investigation team assignment
   - Root cause analysis (5 Whys, Fishbone)
   - Corrective and preventive actions
   - Timeline tracking

3. **Safety Dashboard:**
   - Display LTI count (target: 0)
   - Show total days lost
   - List ongoing investigations
   - Alert on any LTI occurrence

4. **Safety Reports:**
   - Monthly safety summary (LTI, First Aid, Near Miss)
   - Incident rate calculation: (Incidents / Total Hours Worked) × 200,000
   - Trending analysis (improving/worsening)
   - Root cause Pareto chart

**Database Schema:**
```sql
CREATE TABLE safety_incidents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    reported_by_gpid VARCHAR(255) NOT NULL,
    injured_person_gpid VARCHAR(255),
    injured_person_name VARCHAR(255),
    incident_type ENUM('LTI', 'first_aid', 'near_miss') NOT NULL,
    severity ENUM('fatal', 'serious', 'moderate', 'minor') NOT NULL,
    location VARCHAR(255) NOT NULL,
    area_id BIGINT UNSIGNED,
    description TEXT NOT NULL,
    immediate_action TEXT,
    days_lost INT DEFAULT 0,
    status ENUM('reported', 'investigating', 'root_cause_identified', 'corrective_actions', 'closed') DEFAULT 'reported',
    root_cause TEXT,
    corrective_actions TEXT,
    preventive_actions TEXT,
    investigation_team JSON, -- [gpid1, gpid2]
    closed_date DATE,
    photos JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- All users: Report incidents
- Managers/AM/Safety: Investigate and close
- Super Admin: Full access

---

### 12. EHS Observation Compliance: Min. 4 per Month ❌
**Status:** NOT SUPPORTED  
**Target:** Minimum 4 EHS (Environment, Health, Safety) observations per month

**Required Features:**
1. **EHS Observation System:**
   - Observation form (date, location, observer, observation type)
   - Observation types: Safe Act, Unsafe Act, Safe Condition, Unsafe Condition
   - Category: PPE, Housekeeping, Equipment, Procedure, Environment
   - Risk level: Critical, High, Medium, Low
   - Photo attachment support

2. **Observation Workflow:**
   - Status: Observed → Action Required → In Progress → Resolved → Verified
   - Auto-assign to responsible person
   - Action deadline based on risk level
   - Verification by EHS team or manager

3. **EHS Dashboard:**
   - Display total observations per person
   - Show compliance rate (>= 4 per month)
   - List pending actions
   - Alert when below target

4. **EHS Reports:**
   - Monthly observation summary (safe vs unsafe)
   - Top unsafe behaviors/conditions (top 5)
   - Resolution rate: (Resolved / Total Observed) × 100
   - Observation trends (improving/worsening)

**Database Schema:**
```sql
CREATE TABLE ehs_observations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    observed_by_gpid VARCHAR(255) NOT NULL,
    observation_date DATE NOT NULL,
    observation_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    area_id BIGINT UNSIGNED,
    observation_type ENUM('safe_act', 'unsafe_act', 'safe_condition', 'unsafe_condition') NOT NULL,
    category ENUM('ppe', 'housekeeping', 'equipment', 'procedure', 'environment') NOT NULL,
    risk_level ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('observed', 'action_required', 'in_progress', 'resolved', 'verified') DEFAULT 'observed',
    action_required TEXT,
    assigned_to_gpid VARCHAR(255),
    action_deadline DATE,
    resolved_date DATE,
    resolution_notes TEXT,
    verified_by_gpid VARCHAR(255),
    photos JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (observed_by_gpid) REFERENCES users(gpid),
    FOREIGN KEY (assigned_to_gpid) REFERENCES users(gpid),
    FOREIGN KEY (verified_by_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- All users: Submit observations
- Assigned users: Resolve actions
- Managers/EHS: Verify resolutions

---

### 13. WO Improvement on Area Ownership: Min. 5 (Per Area/Per Month) ❌
**Status:** NOT SUPPORTED  
**Target:** Minimum 5 Work Order improvements per area owner per month

**Required Features:**
1. **Area Ownership System:**
   - Assign area owners (technician/engineer responsible for specific areas)
   - Link users to areas in `area_owners` table
   - Display area ownership in user profile

2. **WO Improvement Tracking:**
   - Track improvements made per Work Order (time saved, cost reduced, recurrence prevented)
   - Improvement types: Process optimization, Spare part standardization, Procedure update, Training provided
   - Link improvement to WO ID

3. **Improvement Dashboard:**
   - Display total improvements per area owner
   - Show improvement rate: (WO with Improvements / Total WO) × 100
   - List pending improvement opportunities
   - Alert when below target (< 5 per month)

4. **Improvement Reports:**
   - Monthly summary per area/owner
   - Impact analysis (time saved, cost reduced)
   - Best practices sharing (top improvements)

**Database Schema:**
```sql
CREATE TABLE area_owners (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    area_id BIGINT UNSIGNED NOT NULL,
    owner_gpid VARCHAR(255) NOT NULL,
    assigned_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (owner_gpid) REFERENCES users(gpid),
    UNIQUE KEY unique_area_owner (area_id, owner_gpid, is_active)
);

CREATE TABLE wo_improvements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    work_order_id BIGINT UNSIGNED NOT NULL,
    improved_by_gpid VARCHAR(255) NOT NULL,
    improvement_type ENUM('process_optimization', 'spare_part_standardization', 'procedure_update', 'training_provided') NOT NULL,
    description TEXT NOT NULL,
    time_saved_minutes INT,
    cost_saved DECIMAL(10,2),
    recurrence_prevented BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (improved_by_gpid) REFERENCES users(gpid)
);
```

**Access Control:**
- Area owners: Add improvements to WO in their area
- Managers: View all improvements
- Super Admin: Full access

---

### 14. RCA for Every Downtime NO% Over Target ❌
**Status:** NOT SUPPORTED (Similar to KPI #7 but stricter)  
**Target:** 100% RCA completion for EVERY downtime occurrence over target threshold

**Required Features:**
- Same as KPI #7 (RCA Compliance for Downtime >10 Minutes)
- But with stricter enforcement: 100% completion rate
- Configurable threshold per equipment type
- Manager approval required before WO closure

**Implementation:** See KPI #7 details above

---

### 15. Performance Assessment Integration ❌
**Status:** PARTIALLY SUPPORTED (Need Integration)  
**Target:** Unified KPI dashboard combining all metrics into single performance score

**Required Features:**
1. **Unified KPI Dashboard:**
   - Display all 15 KPIs in one screen
   - Color-coded status (green: on target, yellow: warning, red: below target)
   - Department/person filter
   - Date range filter (daily/weekly/monthly/yearly)

2. **Performance Score Calculation:**
   - Weighted score based on KPI achievement
   - Formula: Σ(KPI Achievement % × KPI Weight) / Total Weight
   - Example weights:
     - PM Compliance: 10%
     - Downtime: 15%
     - MTTR: 10%
     - CBM Compliance: 10%
     - Kaizen: 10%
     - Abnormality Finding: 8%
     - OPL/SOP: 7%
     - RCA Compliance: 10%
     - Safety LTI: 15%
     - EHS Observations: 5%

3. **Performance Comparison:**
   - Individual vs department average
   - Department vs company target
   - Month-over-month trends
   - Year-over-year comparison

4. **Automated Reporting:**
   - Daily summary email to managers
   - Weekly performance digest
   - Monthly comprehensive report
   - Quarterly review dashboard

**Database Schema:**
```sql
CREATE TABLE kpi_targets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    kpi_name VARCHAR(255) NOT NULL,
    kpi_code VARCHAR(50) NOT NULL UNIQUE, -- pm_compliance, mttr, kaizen, etc.
    target_value DECIMAL(10,2) NOT NULL,
    target_operator ENUM('>=', '<=', '=', '>', '<') NOT NULL,
    measurement_unit VARCHAR(50), -- %, minutes, count
    weight DECIMAL(5,2) DEFAULT 10.00, -- for weighted scoring
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE kpi_achievements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    kpi_code VARCHAR(50) NOT NULL,
    user_gpid VARCHAR(255),
    department VARCHAR(255),
    period_type ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    actual_value DECIMAL(10,2) NOT NULL,
    target_value DECIMAL(10,2) NOT NULL,
    achievement_percentage DECIMAL(5,2), -- (actual / target) * 100
    status ENUM('on_target', 'warning', 'below_target') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_gpid) REFERENCES users(gpid),
    UNIQUE KEY unique_kpi_period (kpi_code, user_gpid, period_type, period_start)
);
```

**Access Control:**
- All users: View own KPI dashboard
- Managers: View department KPI dashboard
- Super Admin: View company-wide KPI dashboard

---

## 📊 Implementation Priority Roadmap

**Note:** Phase 31 (KPI Foundation Dashboard) has been removed as KPI visualization will be handled by Power BI integration and existing Technician Performance page needs only data refinement.

### Phase 31: Kaizen & Improvement Tracking (High Priority) - 3 Weeks
**Objective:** Implement Kaizen management and WO improvement tracking

**Features:**
1. ✅ Kaizen Management System (submission, approval, implementation)
2. ✅ Kaizen scoring (RECON=5, DT=3, Safety=1)
3. ✅ WO Improvement Tracking (link improvements to WO)
4. ✅ Area Ownership System (assign owners to areas)
5. ✅ Kaizen Dashboard (count per person, alerts)
6. ✅ WO Improvement Dashboard (count per area owner, alerts)

**Estimated Effort:** 80 hours
- Database migrations: 6 hours
- Models and relationships: 8 hours
- Filament resources (Kaizen + WO Improvements): 20 hours
- Workflow implementation: 12 hours
- Dashboard widgets: 16 hours
- Reports: 12 hours
- Testing: 6 hours

---

### Phase 32: Safety & EHS Tracking (High Priority) - 3 Weeks
**Objective:** Implement Kaizen management and WO improvement tracking

**Features:**
1. ✅ Kaizen Management System (submission, approval, implementation)
2. ✅ Kaizen scoring (RECON=5, DT=3, Safety=1)
3. ✅ WO Improvement Tracking (link improvements to WO)
4. ✅ Area Ownership System (assign owners to areas)
5. ✅ Kaizen Dashboard (count per person, alerts)
6. ✅ WO Improvement Dashboard (count per area owner, alerts)

**Estimated Effort:** 80 hours
- Database migrations: 6 hours
- Models and relationships: 8 hours
- Filament resources (Kaizen + WO Improvements): 20 hours
- Workflow implementation: 12 hours
- Dashboard widgets: 16 hours
- Reports: 12 hours
- Testing: 6 hours

---

### Phase 33: Safety & EHS Tracking (High Priority) - 3 Weeks
**Objective:** Implement safety incident tracking and EHS observation system

**Features:**
1. ✅ Safety Incident Tracking (LTI, First Aid, Near Miss)
2. ✅ Investigation workflow (root cause, corrective actions)
3. ✅ EHS Observation System (safe/unsafe acts/conditions)
4. ✅ Risk assessment and action tracking
5. ✅ Safety Dashboard (LTI count, days lost)
6. ✅ EHS Dashboard (observation count, resolution rate)

**Estimated Effort:** 80 hours
- Database migrations: 6 hours
- Models and relationships: 8 hours
- Filament resources (Safety + EHS): 20 hours
- Workflow implementation: 12 hours
- Dashboard widgets: 16 hours
- Reports: 12 hours
- Testing: 6 hours

---

### Phase 33: Abnormality & Document Management (Medium Priority) - 2 Weeks
**Objective:** Implement abnormality tracking and OPL/SOP document management

**Features:**
1. ✅ Abnormality Tracking (find, assign, fix, verify)
2. ✅ Severity-based deadlines (critical: 24h, high: 3 days, etc.)
3. ✅ Document Management (OPL/SOP creation, approval, publishing)
4. ✅ Rich text editor with image support
5. ✅ Document library (searchable, filterable)
6. ✅ Read acknowledgment tracking

**Estimated Effort:** 60 hours
- Database migrations: 6 hours
- Models and relationships: 8 hours
- Filament resources (Abnormality + Documents): 18 hours
- Workflow implementation: 10 hours
- Dashboard widgets: 10 hours
- Reports: 6 hours
- Testing: 2 hours

---

### Phase 34: CBM & Utility Cost Enhancement (Medium Priority) - 2 Weeks
**Objective:** Enhance CBM tracking and add utility cost per kg calculation

**Features:**
1. ✅ CBM Compliance Tracking (schedule vs execution)
2. ✅ Parameter deviation alerts (threshold monitoring)
3. ✅ Production data tracking (kg produced per shift/day)
4. ✅ Utility consumption tracking (water/electricity/gas meters)
5. ✅ Per-kg cost calculation and dashboard
6. ✅ Alerts when exceeding cost targets

**Estimated Effort:** 60 hours
- Database migrations: 4 hours
- Models and relationships: 6 hours
- CBM schedule service: 8 hours
- Production & utility tracking: 10 hours
- Cost calculation service: 8 hours
- Dashboard widgets: 12 hours
- Reports: 8 hours
- Testing: 4 hours

---

### Phase 35: RCA Enhancement & Integration (Medium Priority) - 1 Week
**Objective:** Enhance RCA compliance tracking and integrate with AI

**Features:**
1. ✅ Mandatory RCA for downtime >10 minutes
2. ✅ RCA compliance tracking (percentage calculation)
3. ✅ AI-powered RCA suggestions (integrate existing AIAnalyticsService)
4. ✅ Auto-flag WO requiring RCA
5. ✅ Prevent WO closure without RCA completion

**Estimated Effort:** 30 hours
- Database schema updates: 3 hours
- Work Order workflow enhancement: 8 hours
- RCA form and resource: 6 hours
- AI integration: 6 hours
- Dashboard widget: 4 hours
- Reports: 2 hours
- Testing: 1 hour

---

## 📈 Total Implementation Estimate

**Total Phases:** 5 phases (31-35)  
**Total Duration:** 11 weeks (2.75 months)  
**Total Effort:** 310 hours (~39 working days)

**Breakdown by Category:**
- High Priority (Phases 31-32): 6 weeks, 160 hours
- Medium Priority (Phases 33-35): 5 weeks, 150 hours

**Resource Requirements:**
- 1 Full-stack Developer (Laravel + Filament + MySQL)
- 1 QA Tester (part-time for testing phases)

**Note:** Phase 31 (KPI Foundation Dashboard) removed as:
- KPI visualization handled by Power BI integration
- Technician Performance page already exists
- Only requires data refinement for existing systems

---

## 🎯 Success Criteria

**Phase 31 Complete When:**
- ✅ Users can submit Kaizens with scoring
- ✅ Managers can approve/reject Kaizens
- ✅ WO improvements linked to Work Orders
- ✅ Area owners assigned and tracked
- ✅ Alerts sent when below targets (< 4 Kaizens, < 5 improvements)

**Phase 32 Complete When:**
- ✅ Safety incidents tracked with investigation workflow
- ✅ LTI count displayed (target: 0)
- ✅ EHS observations tracked with action workflow
- ✅ Compliance calculated (>= 4 observations/month)
- ✅ Alerts sent for any LTI or unsafe conditions

**Phase 33 Complete When:**
- ✅ Abnormalities tracked from finding to verification
- ✅ Fix rate calculated (>= 5 per month per person)
- ✅ OPL/SOP documents created with approval workflow
- ✅ Document library searchable and downloadable
- ✅ Read acknowledgments tracked (>= 2 documents/month)

**Phase 34 Complete When:**
- ✅ CBM compliance calculated (>= 90%)
- ✅ Parameter deviations trigger alerts
- ✅ Production weight tracked per shift/day
- ✅ Utility consumption tracked (water/electricity/gas)
- ✅ Per-kg costs calculated and compared to targets

**Phase 35 Complete When:**
- ✅ RCA mandatory for downtime >10 minutes
- ✅ RCA compliance calculated (>= 90%)
- ✅ AI suggestions provided for root cause analysis
- ✅ WO cannot be closed without RCA completion
- ✅ Alerts sent for pending RCAs

---

## 📝 Notes for Implementation

### Database Design Considerations:
1. **Performance:** Add indexes on frequently queried columns (gpid, date, status)
2. **Data Integrity:** Use foreign keys with ON DELETE CASCADE where appropriate
3. **Audit Trail:** All tables should have created_at and updated_at timestamps
4. **Soft Deletes:** Consider adding deleted_at for records that need retention

### UI/UX Considerations:
1. **Mobile PWA:** Extend existing PWA to include new forms (Kaizen, Abnormality, EHS Observation)
2. **Notifications:** Integrate with existing Telegram/WhatsApp for alerts
3. **Dashboard:** Use existing widget system (Filament ChartWidget/StatsWidget)
4. **Reports:** Reuse existing export functionality (Excel/PDF)

### Integration Points:
1. **Existing Work Order System:** Link improvements, RCA, and abnormalities to WO
2. **Existing User System:** All tracking uses GPID for user identification
3. **Existing Area/Asset System:** Link to existing areas, assets, sub_assets tables
4. **Existing Notification System:** Reuse TelegramService and WhatsAppService
5. **Existing AI Service:** Integrate AIAnalyticsService for RCA suggestions

### Testing Strategy:
1. **Unit Tests:** Models, Services, Calculation logic
2. **Feature Tests:** CRUD operations, Workflows, Permissions
3. **Integration Tests:** KPI calculation, Notifications, Reports
4. **Browser Tests:** Dashboard display, Form submissions, Mobile PWA

---

## 🔗 Related Documentation

- **Current System:** CHECKLIST.md (30 completed phases)
- **Architecture:** ARCHITECTURE.md (database schema, models)
- **Workflow:** WORKFLOW.md (PM and WO workflows)
- **AI Features:** See Phase 21 (AIAnalyticsService implementation)
- **Power BI:** POWERBI_INTEGRATION.md (6 optimized views)
- **PWA:** PWA_MOBILE_GUIDE.md (mobile checklist forms)

---

## ✅ Conclusion

**Current System Coverage:** 26% (4 of 15 KPIs fully supported)  
**With Partial Support:** 46% (7 of 15 KPIs)  
**Gap to Close:** 54% (8 KPIs need new features)

**Recommended Approach:**
1. Start with Phase 31 (Kaizen & Improvement Tracking) - high business impact
2. Implement Phase 32 (Safety & EHS Tracking) - critical for compliance
3. Follow with Phases 33-35 based on business priority

**Total Investment:** ~310 hours (2.75 months) to achieve 100% KPI coverage

**Expected Outcome:** Complete alignment with 2026 Engineering Performance Development KPIs, enabling data-driven decision making and performance tracking across all engineering metrics.

---

**Document Version:** 1.0  
**Last Updated:** December 24, 2025  
**Author:** Nandang Wijaya  
**Copyright © 2025 Nandang Wijaya. All Rights Reserved.**
