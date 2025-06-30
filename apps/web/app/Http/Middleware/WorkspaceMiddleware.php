<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Auto-create workspace for users without one
            if (! $user->current_workspace_id || ! $user->workspaceMemberships()->exists()) {
                $this->ensureUserHasWorkspace($user);
            }

            // Set the current workspace context if user has a current workspace
            if ($user->current_workspace_id) {
                Context::add('workspace_id', $user->current_workspace_id);
            }
        }

        return $next($request);
    }

    /**
     * Ensure user has a workspace, create one if needed
     */
    private function ensureUserHasWorkspace($user): void
    {
        DB::transaction(function () use ($user) {
            // Check if user has any workspace memberships
            $existingMembership = $user->workspaceMemberships()->first();

            if ($existingMembership) {
                // User has memberships but no current workspace set
                $user->update(['current_workspace_id' => $existingMembership->workspace_id]);

                return;
            }

            // User has no workspace memberships, create a new workspace
            $workspace = Workspace::create([
                'name' => "{$user->name}'s Workspace",
                'slug' => Str::slug("{$user->name} Workspace").'-'.Str::random(5),
                'owner_id' => $user->id,
            ]);

            // Create owner membership
            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            // Set as current workspace
            $user->update(['current_workspace_id' => $workspace->id]);
        });
    }
}
