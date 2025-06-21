<?php

namespace App\Policies;

use App\Models\Installation;
use App\Models\User;

class InstallationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view installations if they have a current workspace
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Installation $installation): bool
    {
        // Load the app relationship to check workspace
        $installation->load('app');

        return $installation->app && $user->isMemberOfWorkspace($installation->app->workspace_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All workspace members can create installations
        return $user->current_workspace_id && $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Installation $installation): bool
    {
        // Load the app relationship to check workspace
        $installation->load('app');

        return $installation->app && $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Installation $installation): bool
    {
        // Load the app relationship to check workspace
        $installation->load('app');

        return $installation->app && $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }
}
