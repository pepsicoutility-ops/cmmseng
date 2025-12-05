<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Asset;
use App\Models\SubArea;
use App\Models\SubAsset;
use App\Models\User;

class AreaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Area|SubArea|Asset|SubAsset $record): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Area|SubArea|Asset|SubAsset $record): bool
    {
        return in_array($user->role, ['super_admin', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Area|SubArea|Asset|SubAsset $record): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Area|SubArea|Asset|SubAsset $record): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Area|SubArea|Asset|SubAsset $record): bool
    {
        return $user->role === 'super_admin';
    }
}
