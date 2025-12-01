# PepsiCo Engineering CMMS - System Architecture

**Document Version:** 1.0  
**Last Updated:** November 26, 2025  
**Status:** Pre-Production

---

## 📐 Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Database Schema](#database-schema)
5. [Application Layers](#application-layers)
6. [Data Flow](#data-flow)
7. [Security Architecture](#security-architecture)
8. [Integration Architecture](#integration-architecture)
9. [Deployment Architecture](#deployment-architecture)

---

## 🎯 Overview

PepsiCo Engineering CMMS is a web-based Computerized Maintenance Management System built on modern PHP/Laravel architecture with Filament admin panel. The system follows MVC pattern with additional service layer for business logic and policy-based authorization.

### System Characteristics

- **Architecture Style:** Monolithic Web Application
- **Design Pattern:** MVC with Service Layer
- **Frontend:** Server-side rendered (Livewire) + AJAX
- **Database:** Relational (MySQL)
- **Authentication:** Session-based with GPID
- **Authorization:** Role-based Access Control (RBAC)
- **Deployment:** Traditional LAMP/LEMP stack

---

## 🛠️ Technology Stack

### Backend Stack

```
┌─────────────────────────────────────────┐
│         Application Layer               │
├─────────────────────────────────────────┤
│  Laravel 12 Framework (PHP 8.4)         │
│  - Eloquent ORM                         │
│  - Blade Templating                     │
│  - Queue System                         │
│  - Event Broadcasting                   │
└─────────────────────────────────────────┘
          ↓
┌─────────────────────────────────────────┐
│         Admin Panel Layer               │
├─────────────────────────────────────────┤
│  Filament v4 (Livewire 3 + Alpine.js)   │
│  - Resources (CRUD)                     │
│  - Pages (Custom)                       │
│  - Widgets (Dashboard)                  │
│  - Forms & Tables                       │
└─────────────────────────────────────────┘
          ↓
┌─────────────────────────────────────────┐
│         Database Layer                  │
├─────────────────────────────────────────┤
│  MySQL 8.0                              │
│  - InnoDB Engine                        │
│  - Foreign Key Constraints              │
│  - Indexes (60+ optimized)              │
└─────────────────────────────────────────┘
```

### Key Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^12.0 | Core framework |
| filament/filament | ^4.0 | Admin panel |
| spatie/laravel-permission | ^6.23 | RBAC |
| barryvdh/laravel-dompdf | ^3.1 | PDF generation |
| simplesoftwareio/simple-qrcode | ^4.2 | QR code generation |
| pxlrbt/filament-excel | ^3.2 | Excel import/export |
| intervention/image | ^3.11 | Image processing |
| pestphp/pest | ^4.1 | Testing framework |

---

## 🏗️ System Architecture

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ Login    │  │Dashboard │  │Resources │  │ Widgets  │   │
│  │ Page     │  │ Stats    │  │ (CRUD)   │  │ (Charts) │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │Filament  │  │Livewire  │  │ Policies │  │Middleware│   │
│  │Resources │  │Components│  │(AuthZ)   │  │(AuthN)   │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                     Business Logic Layer                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │Services  │  │Observers │  │  Events  │  │  Jobs    │   │
│  │(Core     │  │(Model    │  │(Trigger) │  │(Async)   │   │
│  │ Logic)   │  │ Hooks)   │  │          │  │          │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                      Data Access Layer                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ Models   │  │Relations │  │ Scopes   │  │ Traits   │   │
│  │(Eloquent)│  │(HasMany, │  │(Query    │  │(Reusable)│   │
│  │          │  │BelongsTo)│  │Filters)  │  │          │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                      Database Layer                          │
│  MySQL 8.0 (InnoDB)                                         │
│  - 30 Tables                                                │
│  - 60+ Indexes                                              │
│  - Foreign Key Constraints                                  │
│  - 6 Optimized Views (Power BI)                             │
└─────────────────────────────────────────────────────────────┘
```

### External Integrations

```
┌─────────────────────────────────────────────────────────────┐
│                    CMMS Application                          │
└─────────────────────────────────────────────────────────────┘
       ↓                    ↓                    ↓
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Power BI   │     │   QR Code   │     │  Email/SMS  │
│  Dashboards │     │  Scanners   │     │ Notifications│
│  (Analytics)│     │ (Operators) │     │  (Alerts)   │
└─────────────┘     └─────────────┘     └─────────────┘
```

---

## 🗄️ Database Schema

### Entity Relationship Overview

```
┌──────────────────────────────────────────────────────────────┐
│                    Equipment Hierarchy                        │
├──────────────────────────────────────────────────────────────┤
│  Areas (1)                                                   │
│    ↓ hasMany                                                 │
│  SubAreas (*)                                                │
│    ↓ hasMany                                                 │
│  Assets (*)                                                  │
│    ↓ hasMany                                                 │
│  SubAssets (*)                                               │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    Work Order Domain                          │
├──────────────────────────────────────────────────────────────┤
│  WorkOrders                                                  │
│    ↓ belongsTo → Area, SubArea, Asset, SubAsset             │
│    ↓ belongsTo → User (created_by_gpid)                     │
│    ↓ hasOne → WoCost                                        │
│    ↓ hasMany → WoProcess                                    │
│    ↓ hasMany → WoPartsUsage                                 │
│    ↓ hasMany → WoImages                                     │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                  Preventive Maintenance Domain                │
├──────────────────────────────────────────────────────────────┤
│  PmSchedules                                                 │
│    ↓ belongsTo → Area, SubArea, Asset, SubAsset             │
│    ↓ belongsTo → User (assigned_by_gpid)                    │
│    ↓ hasMany → PmChecklistItems                             │
│    ↓ hasMany → PmExecutions                                 │
│                                                              │
│  PmExecutions                                                │
│    ↓ belongsTo → PmSchedule                                 │
│    ↓ belongsTo → User (executed_by_gpid)                    │
│    ↓ hasOne → PmCost                                        │
│    ↓ hasMany → PmPartsUsage                                 │
│    ↓ hasOne → PmCompliance                                  │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    Inventory Domain                           │
├──────────────────────────────────────────────────────────────┤
│  Parts (Master Data)                                         │
│    ↓ hasMany → Inventories (by location)                    │
│    ↓ hasMany → InventoryMovements                           │
│    ↓ hasMany → StockAlerts                                  │
│    ↓ hasMany → WoPartsUsage                                 │
│    ↓ hasMany → PmPartsUsage                                 │
│                                                              │
│  Inventories (Location-specific Stock)                       │
│    ↓ belongsTo → Part                                       │
│    ↓ hasMany → InventoryMovements                           │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    User & Permissions Domain                  │
├──────────────────────────────────────────────────────────────┤
│  Users                                                       │
│    ↓ belongsToMany → Roles (via model_has_roles)            │
│    ↓ belongsToMany → Permissions (via model_has_permissions) │
│    ↓ hasMany → WorkOrders (created)                         │
│    ↓ hasMany → PmExecutions (executed)                      │
│    ↓ hasMany → ActivityLogs                                 │
│                                                              │
│  Roles (Super Admin, Manager, Technician, etc.)             │
│    ↓ belongsToMany → Permissions                            │
└──────────────────────────────────────────────────────────────┘
```

### Core Tables (30 Total)

| Table Name | Primary Purpose | Key Relationships |
|------------|----------------|-------------------|
| `users` | User accounts | roles, permissions |
| `areas` | Top-level equipment location | sub_areas, assets |
| `sub_areas` | Equipment subdivision | areas, assets |
| `assets` | Main equipment | areas, sub_areas, sub_assets, work_orders |
| `sub_assets` | Equipment components | assets, work_orders |
| `work_orders` | WO lifecycle tracking | assets, users, costs, processes |
| `wo_costs` | WO cost breakdown | work_orders |
| `wo_processes` | WO processing steps | work_orders, users |
| `wo_parts_usages` | Parts used in WO | work_orders, parts |
| `wo_images` | WO attachments | work_orders |
| `pm_schedules` | PM schedule definitions | assets, users, checklists |
| `pm_executions` | PM execution records | pm_schedules, users, costs |
| `pm_costs` | PM cost breakdown | pm_executions |
| `pm_checklist_items` | PM checklist templates | pm_schedules |
| `pm_compliances` | PM compliance tracking | pm_executions |
| `pm_parts_usages` | Parts used in PM | pm_executions, parts |
| `parts` | Parts master data | inventories, movements |
| `inventories` | Location-based stock | parts, movements |
| `inventory_movements` | Stock movement history | parts, inventories |
| `stock_alerts` | Low stock notifications | parts |
| `running_hours` | Equipment runtime tracking | assets |
| `barcode_tokens` | QR code authentication | work_orders |
| `activity_logs` | Audit trail | users |
| `roles` | RBAC roles | permissions |
| `permissions` | RBAC permissions | roles |
| `model_has_roles` | User-role assignments | users, roles |
| `model_has_permissions` | Direct user permissions | users, permissions |
| `role_has_permissions` | Role-permission assignments | roles, permissions |

### Database Indexes (60+)

**Primary Keys:** All tables have auto-increment `id` primary key

**Foreign Keys:** All relationships enforced with FK constraints

**Performance Indexes:**
- `work_orders`: (status, created_at), (asset_id, status), (assign_to, status)
- `pm_executions`: (scheduled_date, status), (pm_schedule_id, is_on_time)
- `parts`: (current_stock, min_stock), (category, location)
- `inventories`: (part_id, quantity), (location, part_id)
- `users`: (gpid), (department, role), (is_active)
- `activity_logs`: (causer_id, created_at), (subject_type, subject_id)

---

## 🔄 Application Layers

### 1. Presentation Layer (Filament)

**Location:** `app/Filament/`

```php
app/Filament/
├── Pages/               # Custom pages
│   ├── Dashboard.php    # Main dashboard with widgets
│   └── Auth/           # Login, Register, Password Reset
├── Resources/           # CRUD resources
│   ├── WorkOrderResource.php
│   ├── PmScheduleResource.php
│   ├── PartResource.php
│   └── UserResource.php
├── Widgets/             # Dashboard widgets
│   ├── WoStatsWidget.php
│   ├── PmComplianceWidget.php
│   └── StockAlertsWidget.php
└── Imports/             # Excel import handlers
    └── UserImport.php
```

**Responsibilities:**
- Form rendering and validation
- Table display with filters/actions
- Widget data aggregation
- User input handling
- View composition

### 2. Application Layer (Controllers & Middleware)

**Location:** `app/Http/`

```php
app/Http/
├── Controllers/
│   └── Auth/           # Authentication controllers
├── Middleware/
│   ├── Authenticate.php
│   ├── CheckRole.php
│   └── LogActivity.php
└── Requests/           # Form request validation (if needed)
```

**Responsibilities:**
- HTTP request/response handling
- Authentication & session management
- Middleware execution (auth, CORS, etc.)
- Route protection

### 3. Business Logic Layer (Services & Observers)

**Location:** `app/Services/`, `app/Observers/`

```php
app/Services/
├── PmService.php        # PM execution logic, compliance calculation
├── WoService.php        # WO status transitions, MTTR calculation
├── InventoryService.php # Stock sync, movement tracking
└── CostService.php      # Cost calculation logic

app/Observers/
├── WorkOrderObserver.php       # Auto-sync inventory, calculate costs
├── PmExecutionObserver.php     # Update compliance, sync parts
├── PartObserver.php            # Sync with inventories table
└── InventoryObserver.php       # Track movements, trigger alerts
```

**Responsibilities:**
- Core business logic (PM compliance %, MTTR calculation)
- Data transformation and validation
- Complex calculations (costs, performance scores)
- Event handling (created, updated, deleted)
- Cross-module synchronization (Parts ↔ Inventories)

### 4. Data Access Layer (Models & Repositories)

**Location:** `app/Models/`

```php
app/Models/
├── User.php              # HasRoles, HasPermissions
├── WorkOrder.php         # Relationships, scopes, accessors
├── PmSchedule.php        # Schedule logic, date calculations
├── Part.php              # Stock calculations, alerts
├── Inventory.php         # Location-based stock
└── ActivityLog.php       # Audit trail
```

**Key Traits:**
- `HasFactory` - Factory pattern for testing
- `SoftDeletes` - Soft delete support
- `LogsActivity` - Custom trait for audit logging
- `HasRoles`, `HasPermissions` - Spatie RBAC

**Eloquent Relationships:**
- `belongsTo`, `hasMany`, `hasOne` - Standard relationships
- `belongsToMany` - Many-to-many (Users ↔ Roles)
- Polymorphic relationships for activity logs

### 5. Authorization Layer (Policies)

**Location:** `app/Policies/`

```php
app/Policies/
├── WorkOrderPolicy.php       # viewAny, create, update, delete
├── PmSchedulePolicy.php      # Department-based access
├── PartPolicy.php            # Tech store access
└── UserPolicy.php            # Super admin only
```

**Authorization Flow:**
```
Request → Middleware (AuthN) → Policy (AuthZ) → Controller → Service → Model
```

**Policy Methods:**
- `viewAny()` - Can view list?
- `view()` - Can view specific record?
- `create()` - Can create new record?
- `update()` - Can edit record?
- `delete()` - Can delete record?
- Custom: `approve()`, `close()`, `assign()`, etc.

---

## 📊 Data Flow

### Work Order Lifecycle

```
┌─────────────────────────────────────────────────────────────┐
│  1. OPERATOR submits WO via Barcode/Manual form             │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. WorkOrderObserver::creating()                           │
│     - Generate wo_number (auto-increment)                   │
│     - Set status = 'submitted'                              │
│     - Log activity                                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. MANAGER reviews & approves                              │
│     - Policy: WorkOrderPolicy::approve()                    │
│     - Update status → 'reviewed' → 'approved'               │
│     - Assign to department                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. ASSISTANT MANAGER assigns to TECHNICIAN                 │
│     - Create WoProcess record                               │
│     - Set performed_by_gpid                                 │
│     - Update status → 'in_progress'                         │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  5. TECHNICIAN executes work                                │
│     - Record parts usage (WoPartsUsage)                     │
│     - PartObserver auto-decrements stock                    │
│     - Upload images (WoImages)                              │
│     - Set started_at, completed_at                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  6. WoService::calculateMTTR()                              │
│     - MTTR = completed_at - started_at (minutes)            │
│     - Update work_orders.mttr                               │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  7. CostService::calculateWoCost()                          │
│     - labour_cost = hours * rate                            │
│     - parts_cost = SUM(parts_usages.total_cost)             │
│     - downtime_cost = downtime_minutes * rate               │
│     - Save to wo_costs table                                │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  8. MANAGER closes WO                                       │
│     - Update status → 'closed'                              │
│     - Set closed_at timestamp                               │
│     - Generate PDF report (optional)                        │
└─────────────────────────────────────────────────────────────┘
```

### PM Execution Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. PmSchedule created by MANAGER                           │
│     - Define frequency (daily/weekly/monthly/etc.)          │
│     - Assign to department & technician                     │
│     - Add checklist items                                   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Automated PM generation (Artisan command/cron)          │
│     - php artisan pm:generate                               │
│     - Creates PmExecution for upcoming dates                │
│     - Status = 'pending'                                    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. TECHNICIAN executes PM                                  │
│     - Check off checklist items                             │
│     - Record parts usage (PmPartsUsage)                     │
│     - Set actual_start, actual_end                          │
│     - Calculate duration (minutes)                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. PmService::calculateCompliance()                        │
│     - is_on_time = (actual_end <= scheduled_date + grace)   │
│     - Update pm_executions.is_on_time                       │
│     - Create PmCompliance record                            │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  5. CostService::calculatePmCost()                          │
│     - labour_cost = duration * rate                         │
│     - parts_cost = SUM(parts_usages.total_cost)             │
│     - overhead_cost = fixed_rate                            │
│     - Save to pm_costs table                                │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  6. PmService::calculatePerformanceScore()                  │
│     - Compliance score (40 pts)                             │
│     - Workload score (30 pts)                               │
│     - Activity score (30 pts)                               │
│     - Total performance_score (max 100)                     │
└─────────────────────────────────────────────────────────────┘
```

### Inventory Sync Flow

```
┌─────────────────────────────────────────────────────────────┐
│  Parts Table (Master Data)                                  │
│  - part_number, name, current_stock, min_stock, unit_price  │
└─────────────────────────────────────────────────────────────┘
       ↓ (Two-way sync)         ↑
┌─────────────────────────────────────────────────────────────┐
│  Inventories Table (Location-specific)                      │
│  - part_id, location, quantity, unit_price                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  PartObserver / InventoryObserver                           │
│  - When Part.current_stock changes → update Inventories     │
│  - When Inventory.quantity changes → update Part            │
│  - Create InventoryMovement record                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  Stock Alert Logic                                          │
│  - IF current_stock <= min_stock THEN create StockAlert     │
│  - Notify tech store staff                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔒 Security Architecture

### Authentication Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. User visits /pep/login                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Enter GPID + Password                                   │
│     - GPID format validation (alphanumeric)                 │
│     - Password hashed with bcrypt                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Filament AuthN (Laravel Session)                        │
│     - Query users table (gpid, password_hash)               │
│     - Verify password_verify()                              │
│     - Check is_active = 1                                   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Session Created                                         │
│     - Store user_id in session                              │
│     - Generate CSRF token                                   │
│     - Set remember_token (if checked)                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  5. Load User Roles & Permissions (Spatie)                  │
│     - Query model_has_roles → roles                         │
│     - Query role_has_permissions → permissions              │
│     - Cache in session                                      │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  6. Redirect to /pep (Dashboard)                            │
└─────────────────────────────────────────────────────────────┘
```

### Authorization Flow (RBAC)

```
┌─────────────────────────────────────────────────────────────┐
│  User → Request (e.g., Edit Work Order)                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  Middleware: auth (Authenticate.php)                        │
│  - Check session exists                                     │
│  - Load authenticated user                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  Policy: WorkOrderPolicy::update($user, $workOrder)         │
│  - IF user.role = 'super_admin' → ALLOW                     │
│  - IF user.role = 'manager' → ALLOW                         │
│  - IF user.department = workOrder.assign_to → ALLOW         │
│  - ELSE → DENY (403 Forbidden)                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  Authorization Result                                       │
│  - ALLOW → Proceed to controller/service                    │
│  - DENY → Return 403 error or hide UI element               │
└─────────────────────────────────────────────────────────────┘
```

### Security Layers

| Layer | Protection | Implementation |
|-------|-----------|----------------|
| **Input Validation** | XSS, Injection | Filament form validation, Laravel validation rules |
| **Authentication** | Unauthorized access | Laravel session + GPID authentication |
| **Authorization** | Privilege escalation | Spatie Permission + Policies |
| **CSRF** | Cross-site request forgery | Laravel CSRF tokens on all forms |
| **SQL Injection** | Database attacks | Eloquent ORM (prepared statements) |
| **Password Security** | Weak passwords | bcrypt hashing, password rules |
| **Data Protection** | Data leaks | Soft deletes, activity logging |
| **Session Security** | Session hijacking | Secure cookies, HttpOnly, SameSite |

---

## 🔗 Integration Architecture

### Power BI Integration (Option 1: Direct Database)

```
┌─────────────────────────────────────────────────────────────┐
│            Power BI Desktop / Service                        │
└─────────────────────────────────────────────────────────────┘
                          ↓ (MySQL Connector)
┌─────────────────────────────────────────────────────────────┐
│         MySQL Database (cmmseng)                            │
│  User: powerbi_readonly (SELECT only)                       │
└─────────────────────────────────────────────────────────────┘
                          ↓ (Query Views)
┌─────────────────────────────────────────────────────────────┐
│  Optimized Database Views (6 views)                         │
│  - vw_powerbi_work_orders                                   │
│  - vw_powerbi_pm_compliance                                 │
│  - vw_powerbi_inventory                                     │
│  - vw_powerbi_equipment                                     │
│  - vw_powerbi_costs                                         │
│  - vw_powerbi_technician_performance                        │
└─────────────────────────────────────────────────────────────┘
                          ↓ (Load Data)
┌─────────────────────────────────────────────────────────────┐
│  Power BI Data Model                                        │
│  - Fact tables (WO, PM Executions, Costs)                   │
│  - Dimension tables (Assets, Users, Dates)                  │
│  - Relationships (star schema)                              │
│  - DAX measures (20+ KPIs)                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓ (Publish)
┌─────────────────────────────────────────────────────────────┐
│  Power BI Service (Cloud)                                   │
│  - Scheduled refresh (via Gateway)                          │
│  - Share dashboards with stakeholders                       │
└─────────────────────────────────────────────────────────────┘
```

### Security Configuration

```
┌─────────────────────────────────────────────────────────────┐
│  VPS Server (Production)                                    │
│  - MySQL bind-address = 0.0.0.0 (allow external)            │
│  - Firewall: Allow 3306 from Power BI Gateway IP            │
│  - VPN: Recommended for extra security                      │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  MySQL User: powerbi_readonly                               │
│  - GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'%'      │
│  - No INSERT, UPDATE, DELETE privileges                     │
│  - Password rotation every 90 days                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Deployment Architecture

### Production Environment (VPS)

```
┌─────────────────────────────────────────────────────────────┐
│                    Load Balancer (Optional)                  │
│                    - Nginx Reverse Proxy                     │
│                    - SSL Termination                         │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    Web Server (VPS)                          │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Nginx / Apache                                         │ │
│  │ - Serve static files (CSS, JS, images)                │ │
│  │ - Proxy PHP requests to PHP-FPM                       │ │
│  └────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ PHP-FPM 8.4                                            │ │
│  │ - Process Laravel application                          │ │
│  │ - OPcache enabled                                      │ │
│  │ - Memory limit: 512M                                   │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    Database Server                           │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ MySQL 8.0                                              │ │
│  │ - InnoDB buffer pool: 2GB                              │ │
│  │ - Max connections: 200                                 │ │
│  │ - Daily backups (mysqldump + binlog)                   │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    File Storage                              │
│  - Local: /var/www/cmmseng/storage                         │
│  - Uploads: WO images, PM attachments                       │
│  - Generated: PDFs, QR codes, Excel exports                 │
│  - Backup: Daily rsync to backup server                     │
└─────────────────────────────────────────────────────────────┘
```

### Deployment Stack

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **OS** | Ubuntu 22.04 LTS | Server operating system |
| **Web Server** | Nginx 1.24 | HTTP server, reverse proxy |
| **PHP** | PHP-FPM 8.4 | Application runtime |
| **Database** | MySQL 8.0 | Data persistence |
| **Process Manager** | Supervisor | Queue worker management |
| **SSL/TLS** | Let's Encrypt | HTTPS encryption |
| **Backup** | Cron + rsync | Automated backups |
| **Monitoring** | Uptime Kuma / Sentry | Uptime & error tracking |

### Optimization Checklist

```bash
# Production optimizations
php artisan config:cache         # Cache configuration
php artisan route:cache          # Cache routes
php artisan view:cache           # Cache Blade templates
composer install --optimize-autoloader --no-dev  # Optimize autoload

# Database optimizations
- Enable query caching
- Add indexes on frequently queried columns
- Optimize slow queries (use EXPLAIN)

# PHP-FPM tuning
- pm.max_children = 50
- pm.start_servers = 10
- pm.min_spare_servers = 5
- pm.max_spare_servers = 20

# Nginx tuning
- gzip compression enabled
- Browser caching headers (static assets)
- Connection pooling
```

---

## 📈 Scalability Considerations

### Vertical Scaling (Short-term)
- Increase VPS RAM (8GB → 16GB → 32GB)
- Add CPU cores (4 cores → 8 cores)
- Increase database buffer pool
- Optimize PHP-FPM worker processes

### Horizontal Scaling (Long-term)
- Separate database server from web server
- Add read replicas for reporting queries
- Redis cache for session storage
- CDN for static assets

### Performance Bottlenecks
| Bottleneck | Solution |
|-----------|----------|
| **Slow database queries** | Add indexes, optimize joins, use views |
| **High memory usage** | Enable OPcache, increase PHP memory limit |
| **File upload delays** | Use S3/Object Storage, async processing |
| **Dashboard load time** | Cache widget data, lazy load charts |
| **Concurrent users** | Increase PHP-FPM workers, database connections |

---

## 📝 Conclusion

This architecture provides a solid foundation for the PepsiCo Engineering CMMS with:
- ✅ **Separation of Concerns** (MVC + Service Layer)
- ✅ **Role-based Security** (Spatie Permission + Policies)
- ✅ **Scalable Database Design** (30 tables, 60+ indexes, 6 views)
- ✅ **Clean Data Flow** (Models → Services → Observers → Database)
- ✅ **External Integrations** (Power BI, QR codes, notifications)
- ✅ **Production-ready Deployment** (Nginx + PHP-FPM + MySQL)

The system is designed to handle 50-200 concurrent users with room for growth through vertical and horizontal scaling strategies.

---

**Document Maintained By:** PepsiCo Engineering IT Team  
**Next Review:** Pre-deployment (Phase 18)