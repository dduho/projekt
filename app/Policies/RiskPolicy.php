<?php

namespace App\Policies;

use App\Models\Risk;
use App\Models\User;

class RiskPolicy
{
    /**
     * Determine whether the user can view any risks.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view risks');
    }

    /**
     * Determine whether the user can view the risk.
     */
    public function view(User $user, Risk $risk): bool
    {
        return $user->hasPermissionTo('view risks');
    }

    /**
     * Determine whether the user can create risks.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create risks');
    }

    /**
     * Determine whether the user can update the risk.
     */
    public function update(User $user, Risk $risk): bool
    {
        return $user->hasPermissionTo('edit risks');
    }

    /**
     * Determine whether the user can delete the risk.
     */
    public function delete(User $user, Risk $risk): bool
    {
        return $user->hasPermissionTo('delete risks');
    }

    /**
     * Determine whether the user can export risks.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export risks');
    }
}
