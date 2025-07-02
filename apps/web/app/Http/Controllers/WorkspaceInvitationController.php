<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceInvitationService;
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

        $invitationService = app(WorkspaceInvitationService::class);
        $result = $invitationService->acceptInvitationForAuthenticatedUser($invitation, $user);

        if ($result->wasSuccessful()) {
            return redirect()->route('dashboard')->with('success', $result->getMessage());
        }

        if ($result->getFlashType() === 'warning') {
            return redirect()->route('dashboard')->with('warning', $result->getMessage());
        }

        return redirect()->route('home')->withErrors(['error' => $result->getMessage()]);
    }

    /**
     * Accept invitation by token (called from auth controllers)
     */
    public function acceptInvitationByToken(string $token, User $user): \App\Dto\InvitationAcceptanceResult
    {
        $invitationService = app(WorkspaceInvitationService::class);

        return $invitationService->acceptInvitationByToken($token, $user);
    }
}
