<?php

namespace App\Policies;

use App\Models\PmSchedule;
use App\Models\User;

class PmSchedulePolicy
{
    /**
     * Determine whether the user can view any PM schedules.
     * 
     * - Technician: Can view (filtered to their assigned PM)
     * - Asisten Manager: Can view (filtered to their department)
     * - Manager/Super Admin: Can view all
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }

    /**
     * Determine whether the user can view the PM schedule.
     */
    public function view(User $user, PmSchedule $pmSchedule): bool
    {
        // Super admin and manager can view all
        if (in_array($user->role, ['super_admin', 'manager'])) {
            return true;
        }
        
        // Asisten manager can view PM in their department
        if ($user->role === 'asisten_manager') {
            return $pmSchedule->department === $user->department;
        }
        
        // Technician can only view PM assigned to them
        if ($user->role === 'technician') {
            return $pmSchedule->assigned_to_gpid === $user->gpid;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create PM schedules.
     * 
     * Only Asisten Manager, Manager, and Super Admin can create PM schedules.
     * Technicians CANNOT create PM schedules (they only execute).
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }

    /**
     * Determine whether the user can update the PM schedule.
     * 
     * - Technicians CANNOT edit PM schedules
     * - Asisten Manager can edit PM in their department
     * - Manager/Super Admin can edit all
     */
    public function update(User $user, PmSchedule $pmSchedule): bool
    {
        // Technician cannot edit PM schedules
        if ($user->role === 'technician') {
            return false;
        }
        
        // Super admin and manager can edit all
        if (in_array($user->role, ['super_admin', 'manager'])) {
            return true;
        }
        
        // Asisten manager can edit PM in their department
        if ($user->role === 'asisten_manager') {
            return $pmSchedule->department === $user->department;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the PM schedule.
     * 
     * Only Super Admin can delete PM schedules.
     */
    public function delete(User $user, PmSchedule $pmSchedule): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the PM schedule.
     */
    public function restore(User $user, PmSchedule $pmSchedule): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the PM schedule.
     */
    public function forceDelete(User $user, PmSchedule $pmSchedule): bool
    {
        return $user->role === 'super_admin';
    }
}
