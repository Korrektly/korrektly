<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceSwitchController extends Controller
{
    /**
     * Switch the user's current workspace
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'workspace_id' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            return back()->withErrors(['error' => 'User not authenticated']);
        }

        $workspaceId = $request->input('workspace_id');

        // Verify user belongs to this workspace
        $membership = $user->workspaceMemberships()
            ->where('workspace_memberships.workspace_id', $workspaceId)
            ->first();

        if (! $membership) {
            return back()->withErrors(['error' => 'You do not have access to this workspace']);
        }

        // Update user's current workspace
        $user->current_workspace_id = $workspaceId;
        $user->save();

        return back()->with('success', 'Workspace switched successfully');
    }
}
