<?php

namespace App\Policies;

use App\Models\EquipmentTrouble;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EquipmentTroublePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua role bisa akses menu Equipment Troubles
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EquipmentTrouble $equipmentTrouble): bool
    {
        // Semua role bisa view equipment trouble
        // Karena alurnya technician yang buat, asisten manager yang assign
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Semua role bisa create trouble
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager', 'technician']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EquipmentTrouble $equipmentTrouble): bool
    {
        // Super admin, manager, asisten_manager bisa update semua
        if (in_array($user->role, ['super_admin', 'manager', 'asisten_manager'])) {
            return true;
        }

        // Technician hanya bisa update trouble yang di-assign ke mereka (via pivot table)
        if ($user->role === 'technician') {
            return $equipmentTrouble->technicians->contains($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EquipmentTrouble $equipmentTrouble): bool
    {
        // Hanya super_admin, manager, asisten_manager yang bisa delete
        // Technician TIDAK BISA delete
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EquipmentTrouble $equipmentTrouble): bool
    {
        return in_array($user->role, ['super_admin', 'manager', 'asisten_manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EquipmentTrouble $equipmentTrouble): bool
    {
        return $user->role === 'super_admin';
    }
}
