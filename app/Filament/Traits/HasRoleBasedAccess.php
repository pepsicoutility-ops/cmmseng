<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait HasRoleBasedAccess
 * 
 * Centralized role-based access control for Filament Resources.
 * Eliminates duplicate canAccess() implementations across 25+ resources.
 * 
 * Available Roles:
 * - super_admin: Full system access
 * - manager: Management level access
 * - asisten_manager: Assistant manager access  
 * - technician: Technical staff access
 * - tech_store: Inventory/store access
 * - operator: Limited operational access
 * 
 * @package App\Filament\Traits
 */
trait HasRoleBasedAccess
{
    /**
     * Pattern 1: Admin Only
     * Access for super_admin and manager roles.
     * 
     * Used by: UserResource, ActivityLogResource, WhatsAppSettingResource, 
     *          TechnicianPerformanceResource, AreaResource, SubAreaResource, etc.
     */
    protected static function canAccessAdminOnly(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Pattern 2: Management Level
     * Access for management hierarchy.
     * 
     * Used by: PmScheduleResource, PmComplianceResource, CbmScheduleResource, 
     *          RootCauseAnalysisResource
     */
    protected static function canAccessManagement(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }

    /**
     * Pattern 3: Management + Technician
     * Access for management and technical staff.
     * 
     * Used by: PmExecutionResource, PmReportResource
     */
    protected static function canAccessManagementAndTechnician(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }

    /**
     * Pattern 4: Full Access (All Roles)
     * Access for all authenticated users including operators.
     * 
     * Used by: WorkOrderResource
     */
    protected static function canAccessAllRoles(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician', 'operator']);
    }

    /**
     * Pattern 5: Inventory/Store Access
     * Access for inventory management roles.
     * 
     * Used by: PartResource, InventoryResource, InventoryMovementResource, StockAlertResource
     */
    protected static function canAccessInventory(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager', 'tech_store']);
    }

    /**
     * Pattern 6: Exclude Operator
     * Access for all roles except operator.
     * 
     * Used by: AssetResource, SubAssetResource, WoReportResource, 
     *          UtilityConsumptionResource, ProductionRecordResource, Dashboard
     */
    protected static function canAccessExcludeOperator(): bool
    {
        $user = Auth::user();
        return $user && $user->role !== 'operator';
    }
    
    /**
     * Helper: Check if current user has specific role(s)
     * 
     * @param string|array $roles Single role or array of roles
     * @return bool
     */
    protected static function userHasRole(string|array $roles): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user->role, $roles);
    }
}
