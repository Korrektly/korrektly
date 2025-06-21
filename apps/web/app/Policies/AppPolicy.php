<?php

namespace App\Policies;

use App\Models\App;
use App\Models\User;

class AppPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view apps if they have a current workspace
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->isMemberOfWorkspace($user->current_workspace_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users can create apps if they have a current workspace and can manage it
        return $user->current_workspace_id && $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, App $app): bool
    {
        return $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, App $app): bool
    {
        return $user->hasSomePermissions(['*'], $user->current_workspace_id);
    }
}
