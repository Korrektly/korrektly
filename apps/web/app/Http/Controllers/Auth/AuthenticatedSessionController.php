<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'invitation' => $request->query('invitation'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Handle workspace invitation if present
        $invitationToken = $request->input('invitation');
        if ($invitationToken) {
            $invitationService = app(WorkspaceInvitationService::class);
            $result = $invitationService->acceptInvitationByToken($invitationToken, $user);

            $message = $result->wasSuccessful()
                ? 'Successfully joined the workspace!'
                : 'Could not join workspace. The invitation may be invalid or expired.';

            $messageType = $result->wasSuccessful() ? 'success' : 'warning';

            return redirect()->intended(route('dashboard', absolute: false))
                ->with($messageType, $message);
        }

        // Set current workspace to the first workspace membership if available (normal login flow)
        $firstMembership = $user->workspaceMemberships()->first();
        if ($firstMembership) {
            $user->current_workspace_id = $firstMembership->workspace_id;
            $user->save();
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
