<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\InvitationAcceptanceResult;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;

class WorkspaceInvitationService
{
    /**
     * Accept an invitation by token for a given user
     */
    public function acceptInvitationByToken(string $token, User $user): InvitationAcceptanceResult
    {
        $invitation = WorkspaceInvitation::where('token', $token)->first();

        if (! $invitation) {
            return new InvitationAcceptanceResult(
                success: false,
                message: 'Invalid invitation token',
                type: 'error'
            );
        }

        if (! $invitation->isValid()) {
            $message = $invitation->isExpired()
                ? 'This invitation has expired'
                : 'This invitation has already been accepted';

            return new InvitationAcceptanceResult(
                success: false,
                message: $message,
                type: 'error'
            );
        }

        // Check if the invitation email matches the user
        if ($user->email !== $invitation->email) {
            return new InvitationAcceptanceResult(
                success: false,
                message: 'This invitation is for a different email address',
                type: 'error'
            );
        }

        // Check if user is already a member
        $existingMembership = WorkspaceMembership::where('workspace_id', $invitation->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingMembership) {
            return new InvitationAcceptanceResult(
                success: false,
                message: 'You are already a member of this workspace',
                type: 'warning'
            );
        }

        $this->createMembershipAndFinalize($invitation, $user);

        return new InvitationAcceptanceResult(
            success: true,
            message: 'Successfully joined the workspace!',
            type: 'success'
        );
    }

    /**
     * Create workspace membership and finalize invitation acceptance
     */
    private function createMembershipAndFinalize(WorkspaceInvitation $invitation, User $user): void
    {
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
    }

    /**
     * Validate an invitation token
     */
    public function validateInvitationToken(string $token): ?WorkspaceInvitation
    {
        $invitation = WorkspaceInvitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Check if a user can accept a specific invitation
     */
    public function canUserAcceptInvitation(WorkspaceInvitation $invitation, User $user): bool
    {
        // Check email match
        if ($user->email !== $invitation->email) {
            return false;
        }

        // Check if already a member
        return ! WorkspaceMembership::where('workspace_id', $invitation->workspace_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Accept invitation for an already authenticated user
     */
    public function acceptInvitationForAuthenticatedUser(WorkspaceInvitation $invitation, User $user): InvitationAcceptanceResult
    {
        if (! $invitation->isValid()) {
            $message = $invitation->isExpired()
                ? 'This invitation has expired'
                : 'This invitation has already been accepted';

            return new InvitationAcceptanceResult(
                success: false,
                message: $message,
                type: 'error'
            );
        }

        if (! $this->canUserAcceptInvitation($invitation, $user)) {
            if ($user->email !== $invitation->email) {
                return new InvitationAcceptanceResult(
                    success: false,
                    message: 'This invitation is for a different email address. Please log out and try again.',
                    type: 'error'
                );
            }

            return new InvitationAcceptanceResult(
                success: false,
                message: 'You are already a member of this workspace.',
                type: 'warning'
            );
        }

        $this->createMembershipAndFinalize($invitation, $user);

        return new InvitationAcceptanceResult(
            success: true,
            message: 'Successfully joined the workspace!',
            type: 'success'
        );
    }
}
