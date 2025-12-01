# CMMS Test Suite Documentation

**Project:** CMMS (Computerized Maintenance Management System)  
**Testing Framework:** Pest PHP  
**Date Created:** 2025-11-25  
**Created By:** Nandang Wijaya

---

## 📊 Test Suite Summary

### Total Tests Created: **142 tests**
- **Unit Tests:** 94 tests
- **Feature Tests:** 48 tests

### Test Coverage Areas:
1. ✅ Model Relationships (66 tests)
2. ✅ Service Calculations (28 tests)
3. ✅ PM Schedule CRUD (13 tests)
4. ✅ Work Order Workflow (15 tests)
5. ✅ Inventory Management (20 tests)

---

## 🧪 Unit Tests (94 tests)

### 1. User Model Test (11 tests)
**File:** `tests/Unit/Models/UserModelTest.php`

**Tests:**
- ✅ User has correct fillable attributes
- ✅ User password is hidden from array
- ✅ User role is cast to string
- ✅ User is_active is cast to boolean
- ✅ User can check if super admin
- ✅ User can check if manager
- ✅ User can check if asisten manager
- ✅ User can check if technician
- ✅ User can check if tech store
- ✅ User can check if operator
- ✅ User has all required relationships (pmSchedules, pmExecutions, workOrders, woProcesses, inventoryMovements)

**Key Features Tested:**
- Role-based helper methods (isSuperAdmin(), isManager(), etc.)
- Model relationships
- Attribute casting
- Data hiding (password)

---

### 2. Master Data Model Test (11 tests)
**File:** `tests/Unit/Models/MasterDataModelTest.php`

**Tests:**
- ✅ Area has correct fillable attributes and casts
- ✅ Area has subAreas, assets, workOrders, inventories relationships
- ✅ SubArea belongs to Area, has assets relationship
- ✅ Asset belongs to SubArea, has subAssets relationship
- ✅ SubAsset belongs to Asset
- ✅ Cascade relationships work: Area → SubArea → Asset → SubAsset

**Key Features Tested:**
- Hierarchical data structure
- Cascade dropdown support
- Parent-child relationships
- Boolean casting

---

### 3. PM Model Test (14 tests)
**File:** `tests/Unit/Models/PmModelTest.php`

**Tests:**
- ✅ PmSchedule has correct fillable attributes
- ✅ PmSchedule casts dates correctly (next_due_date)
- ✅ PmSchedule belongs to Asset
- ✅ PmSchedule belongs to assigned user (assignedTo via GPID)
- ✅ PmSchedule has executions and checklist items relationships
- ✅ PmExecution belongs to PmSchedule and executedBy user
- ✅ PmExecution has parts usage and cost relationships
- ✅ PmExecution casts checklist and photos to array
- ✅ PmPartsUsage belongs to PmExecution and Part
- ✅ PmCost belongs to PmExecution

**Key Features Tested:**
- PM scheduling relationships
- GPID-based user relationships
- Date/datetime casting
- JSON array casting
- Cost tracking

---

### 4. Work Order Model Test (14 tests)
**File:** `tests/Unit/Models/WorkOrderModelTest.php`

**Tests:**
- ✅ WorkOrder has correct fillable attributes
- ✅ WorkOrder casts dates correctly (reviewed_at, approved_at, started_at, etc.)
- ✅ WorkOrder casts photos to array
- ✅ WorkOrder belongs to Asset and createdBy user
- ✅ WorkOrder has processes, parts usage, and cost relationships
- ✅ WorkOrder can have multiple processes (history tracking)
- ✅ WoProcesse belongs to WorkOrder and performedBy user
- ✅ WoPartsUsage belongs to WorkOrder and Part
- ✅ WoCost belongs to WorkOrder
- ✅ WoCost casts decimal values correctly

**Key Features Tested:**
- Workflow timestamp tracking
- Process history
- Parts usage tracking
- Cost calculation fields
- Decimal precision

---

### 5. Inventory Model Test (16 tests)
**File:** `tests/Unit/Models/InventoryModelTest.php`

**Tests:**
- ✅ Inventory has correct fillable attributes
- ✅ Inventory belongs to Part and Asset
- ✅ Inventory has movements relationship
- ✅ InventoryMovement belongs to Part and performedBy user
- ✅ InventoryMovement has morphTo reference relationship
- ✅ Part has inventories, movements, and stock alerts relationships
- ✅ Part casts prices to decimal
- ✅ StockAlert belongs to Part
- ✅ StockAlert casts is_resolved to boolean
- ✅ StockAlert casts resolved_at to datetime

**Key Features Tested:**
- Multi-location inventory
- Stock movement tracking
- Polymorphic relationships (reference)
- Alert system
- Price precision

---

### 6. WO Service Test (8 tests)
**File:** `tests/Unit/Services/WoServiceTest.php`

**Tests:**
- ✅ WO service calculates downtime correctly (30 minutes)
- ✅ WO service rounds up fractional minutes (1.5 min → 2 min)
- ✅ WO service calculates labour cost correctly (60 min = Rp 50,000)
- ✅ WO service calculates parts cost correctly (2 × Rp 100,000 = Rp 200,000)
- ✅ WO service calculates total cost correctly (labour + parts + downtime)
- ✅ WO service sets status to completed
- ✅ MTTR equals downtime (both use start → complete time)

**Key Features Tested:**
- Downtime calculation from process history
- MTTR calculation
- Labour cost (configurable hourly rate)
- Parts cost (unit_price × quantity)
- Downtime cost (configurable rate)
- Rounding logic (ceil)

**Configuration:**
- `cmms.labour_hourly_rate` = 50,000 IDR
- `cmms.downtime_cost_per_hour` = 100,000 IDR

---

### 7. PM Service Test (8 tests)
**File:** `tests/Unit/Services/PmServiceTest.php`

**Tests:**
- ✅ PM service calculates labour cost correctly (60 min = Rp 50,000)
- ✅ PM service calculates labour cost with partial hour (30 min = Rp 25,000)
- ✅ PM service calculates parts cost correctly (sum of all parts usage)
- ✅ PM service calculates overhead cost correctly (10% of labour + parts)
- ✅ PM service calculates total cost correctly (labour + parts + overhead)
- ✅ PM service completes PM execution with cost calculation
- ✅ PM service calculates duration from actual_start and actual_end
- ✅ PM service updates existing cost on recalculation

**Key Features Tested:**
- Duration-based labour cost
- Parts cost aggregation
- Overhead calculation (10% markup)
- Auto duration calculation
- Cost record update (not duplicate)

**Cost Formula:**
```
Labour Cost = (duration_in_minutes / 60) × hourly_rate
Parts Cost = SUM(part.unit_price × quantity)
Overhead Cost = (Labour + Parts) × 0.10
Total Cost = Labour + Parts + Overhead
```

---

### 8. Inventory Service Test (12 tests)
**File:** `tests/Unit/Services/InventoryServiceTest.php`

**Tests:**
- ✅ Inventory service deducts part stock correctly
- ✅ Inventory service creates movement record when deducting
- ✅ Inventory service adds stock correctly
- ✅ Inventory service creates movement record when adding stock
- ✅ Inventory service creates low stock alert when below minimum
- ✅ Inventory service creates out of stock alert when depleted
- ✅ Inventory service resolves alerts when stock is sufficient
- ✅ Inventory service does not create duplicate alerts
- ✅ Inventory service adjusts stock correctly
- ✅ Inventory service tracks movement chronologically
- ✅ Part stock status is calculated correctly
- ✅ Part shows last_restocked_at after adding stock

**Key Features Tested:**
- Stock addition/deduction
- Movement record creation (IN/OUT/ADJUSTMENT)
- Alert creation (low_stock, out_of_stock)
- Alert resolution
- Duplicate alert prevention
- Stock synchronization
- Timestamp tracking

---

## 🎯 Feature Tests (48 tests)

### 1. PM Schedule CRUD Test (13 tests)
**File:** `tests/Feature/PmScheduleCrudTest.php`

**Tests:**
- ✅ Manager can create PM schedule
- ✅ Technician can only view their assigned PM schedules (personalized query)
- ✅ Manager can view all PM schedules
- ✅ PM schedule auto generates code (PM-YYYYMM-###)
- ✅ PM schedule belongs to asset
- ✅ PM schedule belongs to assigned user
- ✅ PM schedule can be updated by manager
- ✅ PM schedule can be deactivated
- ✅ PM schedule with weekly type requires week day
- ✅ PM schedule can filter by department
- ✅ Asisten manager can view department PM schedules only

**Key Features Tested:**
- Role-based access (Manager, Technician, Asisten Manager)
- Personalized queries
- Auto code generation
- Schedule types (weekly, running_hours, cycle)
- Department filtering
- Status management

---

### 2. Work Order Workflow Test (15 tests)
**File:** `tests/Feature/WorkOrderWorkflowTest.php`

**Tests:**
- ✅ Operator can create work order
- ✅ Work order auto generates WO number (WO-YYYYMM-####)
- ✅ Work order starts with pending status
- ✅ Technician can review work order
- ✅ Manager can approve work order
- ✅ Technician can start work after approval
- ✅ Technician can complete work order
- ✅ Manager can close work order
- ✅ Work order tracks complete workflow (6 actions)
- ✅ Work order process history is ordered by timestamp
- ✅ Work order can have photos attached

**Complete Workflow:**
```
1. Create (Operator) → status: pending
2. Review (Technician) → sets reviewed_at
3. Approve (Manager) → sets approved_at
4. Start (Technician) → status: in_progress, sets started_at
5. Complete (Technician) → status: completed, sets completed_at
6. Close (Manager) → status: closed, sets closed_at
```

**Key Features Tested:**
- 7-step workflow execution
- Role-based actions
- Timestamp tracking
- Process history
- Photo attachments (JSON array)
- Status transitions

---

### 3. Inventory Management Test (20 tests)
**File:** `tests/Feature/InventoryManagementTest.php`

**Tests:**
- ✅ Tech store can create inventory record
- ✅ Inventory belongs to part
- ✅ Adding stock creates movement record (type: IN)
- ✅ Adding stock increases part current stock
- ✅ Deducting stock decreases part current stock
- ✅ Deducting stock creates out movement record (type: OUT)
- ✅ Low stock triggers alert (current < min)
- ✅ Out of stock triggers alert (current = 0)
- ✅ Restocking above minimum resolves alert
- ✅ Stock movements are tracked chronologically
- ✅ Part stock status is calculated correctly (sufficient/low/out)
- ✅ Inventory can be adjusted to specific quantity (type: ADJUSTMENT)
- ✅ Multiple inventories for same part sum correctly
- ✅ Inventory location can be updated
- ✅ Stock alert can be manually resolved
- ✅ Part shows last_restocked_at after adding stock

**Alert Logic:**
- **Low Stock:** `current_stock < min_stock && current_stock > 0`
- **Out of Stock:** `current_stock = 0`
- **Resolved:** `current_stock >= min_stock`

**Movement Types:**
- `in` - Stock added (manual or restock)
- `out` - Stock deducted (PM or WO parts usage)
- `adjustment` - Stock adjusted to specific quantity

**Key Features Tested:**
- Multi-location inventory
- Stock movement tracking
- Alert creation and resolution
- Duplicate alert prevention
- Stock synchronization between Parts and Inventories

---

## 🛠️ Test Infrastructure

### Pest PHP Configuration
**File:** `tests/Pest.php`

```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');
```

- Uses `RefreshDatabase` trait for all tests
- Automatically migrates database before each test
- Rolls back changes after each test

### PHPUnit Configuration
**File:** `phpunit.xml`

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="cmmseng_test"/>
```

- Uses MySQL instead of SQLite (PHP 8.4 compatibility)
- Test database: `cmmseng_test`
- Runs migrations on separate test database

### Model Factories Created
1. **AreaFactory** - Random area generation (Proses, Packaging, Utility)
2. **SubAreaFactory** - Random sub area generation (EP, PC, TC, DBM, LBCSS)
3. **AssetFactory** - Random asset with serial numbers and installation dates
4. **SubAssetFactory** - Random sub assets (Fryer, Mixer, Pump, etc.)
5. **PartFactory** - Random parts with stock levels and prices
6. **PmScheduleFactory** - PM schedules with auto code generation
7. **PmExecutionFactory** - PM executions with duration calculation
8. **WorkOrderFactory** - Work orders with auto WO number
9. **WoProcesseFactory** - WO process history records
10. **InventorieFactory** - Inventory records with locations

---

## 📈 Test Execution

### Running All Tests
```bash
php artisan test
```

### Running Specific Test Suite
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Running Specific Test File
```bash
php artisan test tests/Unit/Models/UserModelTest.php
php artisan test tests/Feature/WorkOrderWorkflowTest.php
```

### Running with Coverage
```bash
php artisan test --coverage
```

---

## ✅ Test Results Summary

### Unit Tests: **94/94 Created** ✅
- UserModelTest: 11 tests
- MasterDataModelTest: 11 tests
- PmModelTest: 14 tests
- WorkOrderModelTest: 14 tests
- InventoryModelTest: 16 tests
- WoServiceTest: 8 tests
- PmServiceTest: 8 tests
- InventoryServiceTest: 12 tests

### Feature Tests: **48/48 Created** ✅
- PmScheduleCrudTest: 13 tests
- WorkOrderWorkflowTest: 15 tests
- InventoryManagementTest: 20 tests

### Browser Tests: **0/0** (Pending Dusk installation)

### Total: **142 tests created** ✅

---

## 📝 Next Steps

### Remaining Test Tasks:
1. **Browser Tests** (Laravel Dusk)
   - Install Laravel Dusk
   - Test barcode form submission
   - Test complete PM execution flow
   - Test complete WO flow from barcode to close
   - Test Filament panel navigation

2. **Performance Tests**
   - Bulk data generation (1000+ PM, 10000+ WO)
   - Query optimization testing
   - Database indexing

3. **Security Tests**
   - Policy enforcement
   - Unauthorized access attempts
   - Input validation
   - SQL injection prevention

4. **Manual Testing Checklist**
   - Test each role's access level
   - Verify workflow transitions
   - Check notification delivery
   - Validate calculations

---

## 🔧 Maintenance

### Adding New Tests
1. Create test file in appropriate directory (`tests/Unit` or `tests/Feature`)
2. Use Pest syntax: `test('description', function () { ... })`
3. Use `beforeEach()` for setup
4. Use `RefreshDatabase` trait (automatically included)
5. Use factories for test data generation

### Test Best Practices
- ✅ Use descriptive test names
- ✅ One assertion per test (when possible)
- ✅ Use factories instead of manual data creation
- ✅ Clean up after tests (handled by RefreshDatabase)
- ✅ Test both happy path and edge cases
- ✅ Mock external services (email, notifications, etc.)

---

**Last Updated:** 2025-11-25  
**Updated By:** Nandang Wijaya  
**Status:** Phase 16 Test Suite Complete ✅
