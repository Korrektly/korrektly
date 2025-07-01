<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the workspace overview page
     */
    public function showOverview(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            abort(404, 'No current workspace found');
        }

        // Check if user can manage workspace
        $membership = $user->getMembershipForWorkspace($workspace->id);
        $canManage = $membership && in_array($membership->role, ['owner', 'admin']);

        return Inertia::render('settings/workspace/overview', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'logo' => $workspace->logo,
                'owner_id' => $workspace->owner_id,
            ],
            'canManage' => $canManage,
        ]);
    }

    /**
     * Show the workspace members page
     */
    public function showMembers(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            abort(404, 'No current workspace found');
        }

        // Check if user can manage workspace
        $membership = $user->getMembershipForWorkspace($workspace->id);
        $canManage = $membership && in_array($membership->role, ['owner', 'admin']);

        // Get workspace members with their details
        $members = $workspace->members()
            ->with('user')
            ->get()
            ->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'avatar' => $membership->user->avatar,
                    'role' => $membership->role,
                    'created_at' => $membership->created_at,
                    'is_owner' => $membership->role === 'owner',
                ];
            });

        // Get pending invitations
        $pendingInvitations = $workspace->invitations()
            ->with('inviter')
            ->pending()
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'invited_by' => $invitation->inviter->name,
                    'expires_at' => $invitation->expires_at,
                    'created_at' => $invitation->created_at,
                ];
            });

        return Inertia::render('settings/workspace/members', [
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'canManage' => $canManage,
            'availableRoles' => ['member', 'admin'],
        ]);
    }

    /**
     * Update workspace settings
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            abort(404, 'No current workspace found');
        }

        // Check permissions
        $membership = $user->getMembershipForWorkspace($workspace->id);
        if (! $membership || ! in_array($membership->role, ['owner', 'admin'])) {
            abort(403, 'Insufficient permissions');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace->update($validated);

        return back()->with('success', 'Workspace updated successfully');
    }

    /**
     * Invite a user to the workspace
     */
    public function inviteUser(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            abort(404, 'No current workspace found');
        }

        // Check permissions
        $membership = $user->getMembershipForWorkspace($workspace->id);
        if (! $membership || ! in_array($membership->role, ['owner', 'admin'])) {
            abort(403, 'Insufficient permissions to invite users');
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('workspace_invitations')->where(function ($query) use ($workspace) {
                    return $query->where('workspace_id', $workspace->id)
                        ->whereNull('accepted_at')
                        ->where('expires_at', '>', now());
                }),
            ],
            'role' => 'required|in:member,admin',
        ]);

        // Check if user is already a member
        $existingMember = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->whereHas('user', function ($query) use ($validated) {
                $query->where('email', $validated['email']);
            })
            ->exists();

        if ($existingMember) {
            return back()->withErrors(['email' => 'This user is already a member of the workspace']);
        }

        // Create invitation
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $user->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => WorkspaceInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
                ->notify(new WorkspaceInvitationNotification($invitation));
        } catch (\Exception $e) {
            // Log error but don't fail the invitation
            logger()->error('Failed to send invitation email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Invitation sent successfully');
    }

    /**
     * Update a member's role
     */
    public function updateMemberRole(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace || $membership->workspace_id !== $workspace->id) {
            abort(404, 'Member not found');
        }

        // Check permissions
        $userMembership = $user->getMembershipForWorkspace($workspace->id);
        if (! $userMembership || ! in_array($userMembership->role, ['owner', 'admin'])) {
            abort(403, 'Insufficient permissions');
        }

        $validated = $request->validate([
            'role' => 'required|in:member,admin',
        ]);

        // Prevent users from changing their own role
        if ($membership->user_id === $user->id) {
            return back()->withErrors(['role' => 'You cannot change your own role']);
        }

        // Prevent changing workspace owner
        if ($membership->role === 'owner') {
            return back()->withErrors(['role' => 'Cannot change the workspace owner role']);
        }

        $membership->update(['role' => $validated['role']]);

        return back()->with('success', 'Member role updated successfully');
    }

    /**
     * Remove a member from the workspace
     */
    public function removeMember(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace || $membership->workspace_id !== $workspace->id) {
            abort(404, 'Member not found');
        }

        // Check permissions
        $userMembership = $user->getMembershipForWorkspace($workspace->id);
        if (! $userMembership || ! in_array($userMembership->role, ['owner', 'admin'])) {
            abort(403, 'Insufficient permissions');
        }

        // Prevent removing workspace owner
        if ($membership->role === 'owner') {
            return back()->withErrors(['error' => 'Cannot remove the workspace owner']);
        }

        // Prevent users from removing themselves
        if ($membership->user_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot remove yourself from the workspace']);
        }

        // Remove the member
        $memberUser = $membership->user;
        $membership->delete();

        // If the removed user had this as their current workspace, clear it
        if ($memberUser->current_workspace_id === $workspace->id) {
            // Try to set another workspace as current
            $anotherMembership = $memberUser->workspaceMemberships()->first();
            $memberUser->update([
                'current_workspace_id' => $anotherMembership ? $anotherMembership->workspace_id : null,
            ]);
        }

        return back()->with('success', 'Member removed successfully');
    }

    /**
     * Cancel a pending invitation
     */
    public function cancelInvitation(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace || $invitation->workspace_id !== $workspace->id) {
            abort(404, 'Invitation not found');
        }

        // Check permissions
        $membership = $user->getMembershipForWorkspace($workspace->id);
        if (! $membership || ! in_array($membership->role, ['owner', 'admin'])) {
            abort(403, 'Insufficient permissions');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled successfully');
    }
}
