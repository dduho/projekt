<?php

namespace App\Policies;

use App\Models\ChangeRequest;
use App\Models\User;

class ChangeRequestPolicy
{
    /**
     * Determine whether the user can view any change requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view change-requests');
    }

    /**
     * Determine whether the user can view the change request.
     */
    public function view(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermissionTo('view change-requests');
    }

    /**
     * Determine whether the user can create change requests.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create change-requests');
    }

    /**
     * Determine whether the user can update the change request.
     */
    public function update(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermissionTo('edit change-requests');
    }

    /**
     * Determine whether the user can delete the change request.
     */
    public function delete(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermissionTo('delete change-requests');
    }

    /**
     * Determine whether the user can approve the change request.
     */
    public function approve(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermissionTo('approve change-requests');
    }

    /**
     * Determine whether the user can reject the change request.
     */
    public function reject(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermissionTo('reject change-requests');
    }

    /**
     * Determine whether the user can export change requests.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export change-requests');
    }
}
