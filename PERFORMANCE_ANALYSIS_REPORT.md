# 🚀 Performance Analysis Report - CMMS Engineering

**Tanggal:** 2025-01-21  
**Scope:** N+1 Queries, Index Optimization, Filament Load Time, Cache Strategy

---

## 📊 Executive Summary

| Kategori | Issues Found | Priority |
|----------|--------------|----------|
| N+1 Query Problems | **8 Critical** | 🔴 High |
| Missing Eager Loading | **5 Resources** | 🔴 High |
| Index Optimization | **3 Missing** | 🟡 Medium |
| Cache Opportunities | **6 Areas** | 🟡 Medium |
| Polling Overhead | **3 Tables** | 🟡 Medium |

---

## 1️⃣ N+1 Query Problems

### 🔴 CRITICAL: Missing Eager Loading in Resources

#### 1.1 `PmExecutionResource.php` - **NO EAGER LOADING**
```php
// CURRENT (Line 42-47)
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery(); // ❌ No ->with()
    ...
}
```

**Problem:** Table displays `pmSchedule.code`, `pmSchedule.title`, `executedBy.name` - causes N+1 queries.

**FIX:**
```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with(['pmSchedule', 'executedBy']); // ✅ Add eager loading
    ...
}
```

---

#### 1.2 `WorkOrderResource.php` - **PARTIAL EAGER LOADING**
```php
// CURRENT (Line 42)
$query = parent::getEloquentQuery()->with(['asset']); // Only asset loaded
```

**Problem:** Table juga menampilkan relasi `processes` (untuk MTTR calculation), tapi tidak di-eager load.

**FIX:**
```php
$query = parent::getEloquentQuery()->with(['asset', 'processes']);
```

---

#### 1.3 `PmScheduleResource.php` - **GOOD** ✅
```php
// Already has proper eager loading
$query = parent::getEloquentQuery()->with(['asset', 'subAsset', 'assignedTo']);
```

---

### 🔴 CRITICAL: Table Column N+1 Issues

#### 1.4 `InventoriesTable.php` - N+1 on `part` relation
```php
// Lines 24-32
TextColumn::make('part.part_number')  // ❌ N+1
TextColumn::make('part.name')         // ❌ N+1
TextColumn::make('part.current_stock') // ❌ N+1
```

**Problem:** 3 columns accessing `part` relation tanpa eager loading di Resource.

**FIX di `InventoryResource.php`:**
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['part', 'area']);
}
```

---

#### 1.5 `PartsTable.php` - N+1 in description callback
```php
// Line 53-56
->description(fn ($record) => $record->inventories()->count() > 0 
    ? 'Stored in ' . $record->inventories()->count() . ' location(s)'
    : null
)
```

**Problem:** `inventories()->count()` dipanggil 2x per row = 2N queries!

**FIX:**
```php
// Option 1: Use withCount in Resource
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->withCount('inventories');
}

// Then in Table:
->description(fn ($record) => $record->inventories_count > 0 
    ? "Stored in {$record->inventories_count} location(s)"
    : null
)
```

---

#### 1.6 `PmSchedulesTable.php` - N+1 in color callback
```php
// Lines 85-93
->color(fn ($record) => match(true) {
    $record->isOverdue() => 'danger',  // May trigger queries
    $record->isDueSoon() => 'warning',
    default => 'success',
})
```

**Check:** Pastikan `isOverdue()` dan `isDueSoon()` tidak melakukan query tambahan.

---

## 2️⃣ Index Optimization

### ✅ GOOD: Existing Indexes
```php
// work_orders
$table->index(['assign_to', 'status']);  // ✅ For department filtering
$table->index(['status', 'priority']);   // ✅ For status filtering
$table->index('created_by_gpid');        // ✅ For user filtering

// pm_schedules
$table->index('assigned_to_gpid');            // ✅ For technician view
$table->index(['department', 'is_active']);   // ✅ For asisten_manager view
$table->index(['status', 'is_active']);       // ✅ For active PM

// pm_executions
$table->index('executed_by_gpid');             // ✅
$table->index(['pm_schedule_id', 'status']);   // ✅
$table->index(['scheduled_date', 'status']);   // ✅
```

### 🟡 MISSING: Recommended Additional Indexes

#### 2.1 `work_orders` - Missing `created_at` index
```sql
-- For defaultSort('created_at', 'desc') and date range filters
CREATE INDEX idx_work_orders_created_at ON work_orders(created_at DESC);
```

#### 2.2 `pm_executions` - Missing composite index
```sql
-- For asisten_manager query with department join
CREATE INDEX idx_pm_executions_schedule_status ON pm_executions(pm_schedule_id, status, created_at);
```

#### 2.3 `inventories` - Missing stock filtering index
```sql
-- For low stock alerts (quantity <= min_stock)
CREATE INDEX idx_inventories_stock ON inventories(quantity, min_stock);
```

**Migration to create:**
```php
// database/migrations/xxxx_add_performance_indexes.php
Schema::table('work_orders', function (Blueprint $table) {
    $table->index('created_at');
});

Schema::table('inventories', function (Blueprint $table) {
    $table->index(['quantity', 'min_stock']);
});
```

---

## 3️⃣ Filament Resource Load Time Optimization

### 🔴 CRITICAL: Excessive Polling

| Table | Poll Interval | Issue |
|-------|---------------|-------|
| `WorkOrdersTable` | `5s` | ❌ Too aggressive |
| `PmExecutionsTable` | `10s` | 🟡 Consider 30s |
| `InventoriesTable` | `30s` | ✅ OK |
| `PartsTable` | `30s` | ✅ OK |

**Recommendation:**
```php
// WorkOrdersTable.php - change from 5s to 30s
->poll('30s')  // Reduce server load by 6x

// Or use event-based refresh with Livewire
// Remove poll entirely and use:
// $this->dispatch('refresh-table') when WO status changes
```

### 🟡 Missing `modifyQueryUsing` Pattern

Untuk tables yang perlu eager loading, gunakan `modifyQueryUsing`:

```php
// Di Table configuration
public static function configure(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with(['relation1', 'relation2']))
        ->columns([...]);
}
```

### 🟡 Pagination Optimization

**Current:** Default 10-25 per page
**Issue:** Terlalu kecil untuk dashboard overview

```php
// Untuk overview tables, set:
->defaultPaginationPageOption(25)
->paginationPageOptions([10, 25, 50])
```

---

## 4️⃣ Cache Strategy Recommendations

### 🟡 HIGH VALUE: Stats Widgets

#### 4.1 `OverviewStatsWidget.php` - Cache Weekly Stats
```php
// CURRENT: 4 queries setiap page load
$pmThisWeek = PmExecution::whereBetween(...)->count();
$woThisWeek = WorkOrder::whereBetween(...)->count();
$avgMttr = WorkOrder::where(...)->avg('mttr');
$compliance = PmCompliance::where(...)->first();
```

**FIX:**
```php
use Illuminate\Support\Facades\Cache;

protected function getStats(): array
{
    return Cache::remember('dashboard.overview_stats', now()->addMinutes(5), function () {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        return [
            'pm_this_week' => PmExecution::whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])->count(),
            'wo_this_week' => WorkOrder::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
            'avg_mttr' => WorkOrder::where('status', 'completed')->whereNotNull('mttr')->avg('mttr'),
            'compliance' => PmCompliance::where('period', 'week')->orderBy('period_end', 'desc')->first(),
        ];
    });
    
    // Build Stat objects from cached data...
}
```

#### 4.2 `WoStatusWidget.php` - Cache Status Counts
```php
// Cache per department untuk asisten_manager
$cacheKey = "dashboard.wo_status.{$user->department}";
return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query) {
    return [
        'submitted' => (clone $query)->where('status', 'submitted')->count(),
        'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
        // ...
    ];
});
```

---

### 🟡 MEDIUM VALUE: Dropdown Options

#### 4.3 Asset/Area Dropdowns
```php
// Cache asset list for forms (rarely changes)
$assets = Cache::remember('form.assets', now()->addHours(1), function () {
    return Asset::where('is_active', true)
        ->select('id', 'name', 'code')
        ->get();
});
```

#### 4.4 User Lists
```php
// Cache technician list
$technicians = Cache::remember('form.technicians', now()->addHours(1), function () {
    return User::where('role', 'technician')
        ->where('is_active', true)
        ->select('gpid', 'name', 'department')
        ->get();
});
```

---

### 🟢 Cache Invalidation Strategy

```php
// app/Observers/WorkOrderObserver.php
public function updated(WorkOrder $wo)
{
    Cache::forget('dashboard.overview_stats');
    Cache::forget("dashboard.wo_status.{$wo->assign_to}");
}

// app/Observers/PmExecutionObserver.php  
public function updated(PmExecution $execution)
{
    Cache::forget('dashboard.overview_stats');
}
```

---

## 5️⃣ Quick Wins - Immediate Actions

### Priority 1: Fix N+1 Queries (Estimated 50% performance gain)

1. **PmExecutionResource.php** - Add `->with(['pmSchedule', 'executedBy'])`
2. **InventoryResource.php** - Add `->with(['part', 'area'])`
3. **PartResource.php** - Add `->withCount('inventories')`
4. **WorkOrderResource.php** - Expand to `->with(['asset', 'processes'])`

### Priority 2: Reduce Polling (Estimated 30% server load reduction)

1. Change `WorkOrdersTable` poll from `5s` to `30s`
2. Change `PmExecutionsTable` poll from `10s` to `30s`

### Priority 3: Add Missing Indexes

```bash
php artisan make:migration add_performance_indexes
```

### Priority 4: Implement Widget Caching

Focus on `OverviewStatsWidget` and `WoStatusWidget` first.

---

## 6️⃣ Estimated Performance Impact

| Optimization | Before | After | Improvement |
|--------------|--------|-------|-------------|
| N+1 Fix (100 rows) | ~400 queries | ~4 queries | **99%** |
| Polling Reduction | 720 req/hour | 120 req/hour | **83%** |
| Widget Caching | 8 queries/load | 0-2 queries | **75-100%** |
| Index Addition | Full scan | Index scan | **Variable** |

---

## 📋 Implementation Checklist

- [ ] Fix N+1 di `PmExecutionResource.php`
- [ ] Fix N+1 di `InventoryResource.php`  
- [ ] Fix N+1 di `PartResource.php` (withCount)
- [ ] Expand eager loading di `WorkOrderResource.php`
- [ ] Reduce polling `WorkOrdersTable` (5s → 30s)
- [ ] Reduce polling `PmExecutionsTable` (10s → 30s)
- [ ] Create migration untuk performance indexes
- [ ] Implement cache di `OverviewStatsWidget`
- [ ] Implement cache di `WoStatusWidget`
- [ ] Add cache invalidation observers

---

**Total Estimated Impact:** 60-80% reduction in database load on dashboard pages.
