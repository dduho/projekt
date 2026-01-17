<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create projects');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('edit projects');
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('delete projects');
    }

    /**
     * Determine whether the user can export projects.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export projects');
    }
}
