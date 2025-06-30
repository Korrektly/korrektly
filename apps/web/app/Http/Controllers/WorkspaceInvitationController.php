<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page
     */
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = WorkspaceInvitation::where('token', $token)->first();

        if (! $invitation) {
            return redirect()->route('home')->withErrors(['error' => 'Invalid invitation link']);
        }

        if (! $invitation->isValid()) {
            $message = $invitation->isExpired() ? 'This invitation has expired' : 'This invitation has already been accepted';

            return redirect()->route('home')->withErrors(['error' => $message]);
        }

        // Check if user is already logged in
        if (Auth::check()) {
            return $this->acceptInvitationForLoggedInUser($invitation);
        }

        // Check if user exists but is not logged in
        $existingUser = User::where('email', $invitation->email)->first();

        return Inertia::render('auth/workspace-invitation', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'workspace_name' => $invitation->workspace->name,
                'role' => $invitation->role,
                'inviter_name' => $invitation->inviter->name,
                'expires_at' => $invitation->expires_at,
            ],
            'userExists' => (bool) $existingUser,
            'loginUrl' => route('login', ['invitation' => $invitation->token]),
            'registerUrl' => route('register', ['invitation' => $invitation->token]),
        ]);
    }

    /**
     * Accept invitation for logged-in user
     */
    public function acceptInvitationForLoggedInUser(WorkspaceInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if the invitation email matches the logged-in user
        if ($user->email !== $invitation->email) {
            return redirect()->route('home')->withErrors([
                'error' => 'This invitation is for a different email address. Please log out and try again.',
            ]);
        }

        // Check if user is already a member
        $existingMembership = WorkspaceMembership::where('workspace_id', $invitation->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingMembership) {
            return redirect()->route('dashboard')->withErrors([
                'error' => 'You are already a member of this workspace.',
            ]);
        }

        // Create membership
        WorkspaceMembership::create([
            'workspace_id' => $invitation->workspace_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        // Set as current workspace if user doesn't have one
        if (! $user->current_workspace_id) {
            $user->update(['current_workspace_id' => $invitation->workspace_id]);
        }

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        return redirect()->route('dashboard')->with('success', 'Successfully joined the workspace!');
    }

    /**
     * Accept invitation by token (called from auth controllers)
     */
    public function acceptInvitationByToken(string $token, User $user): bool
    {
        $invitation = WorkspaceInvitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return false;
        }

        // Check if the invitation email matches the user
        if ($user->email !== $invitation->email) {
            return false;
        }

        // Check if user is already a member
        $existingMembership = WorkspaceMembership::where('workspace_id', $invitation->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingMembership) {
            return false;
        }

        // Create membership
        WorkspaceMembership::create([
            'workspace_id' => $invitation->workspace_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        // Set as current workspace if user doesn't have one
        if (! $user->current_workspace_id) {
            $user->update(['current_workspace_id' => $invitation->workspace_id]);
        }

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        return true;
    }
}
