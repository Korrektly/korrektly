<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkspaceInvitationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/register', [
            'invitation' => $request->query('invitation'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! in_array($request->email, config('app.waitlist_emails'))) {
            return redirect()->route('home');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Handle workspace invitation if present
        $this->handleWorkspaceInvitation($request, $user);

        return redirect()->route('dashboard');
    }

    private function handleWorkspaceInvitation(Request $request, User $user)
    {
        $invitationToken = $request->input('invitation');
        if ($invitationToken) {
            $invitationService = app(WorkspaceInvitationService::class);
            $result = $invitationService->acceptInvitationByToken($invitationToken, $user);

            if ($result->wasSuccessful()) {
                return redirect()->route('dashboard')
                    ->with('success', 'Welcome! You have successfully joined the workspace.');
            } else {
                return redirect()->route('dashboard')
                    ->with('warning', 'Account created successfully, but could not join workspace. The invitation may be invalid or expired.');
            }
        }
    }
}
