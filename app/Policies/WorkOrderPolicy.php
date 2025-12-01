<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Auth\Access\Response;

class WorkOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Operators can only view list, others have full access
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician', 'operator']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkOrder $workOrder): bool
    {
        // Operators can view all WOs for monitoring purposes
        if ($user->role === 'operator') {
            return true;
        }
        
        // technician and asisten_manager can view WOs in their department
        if (in_array($user->role, ['technician', 'asisten_manager'])) {
            return $workOrder->assign_to === $user->department;
        }
        
        // Super admin and manager can view all
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Operators can create WOs, plus all other roles
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician', 'operator']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkOrder $workOrder): bool
    {
        // Operators cannot edit WOs
        if ($user->role === 'operator') {
            return false;
        }
        
        // technician and asisten_manager can edit WOs in their department
        if (in_array($user->role, ['technician', 'asisten_manager'])) {
            return $workOrder->assign_to === $user->department;
        }
        
        // Super admin and manager can edit all
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        // Only super_admin and manager can delete
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkOrder $workOrder): bool
    {
        // Only super_admin can restore
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkOrder $workOrder): bool
    {
        // Only super_admin can force delete
        return $user->role === 'super_admin';
    }
}
