<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Models\User;
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
        $invitationToken = $request->input('invitation');
        if ($invitationToken) {
            $invitationController = new WorkspaceInvitationController;
            $accepted = $invitationController->acceptInvitationByToken($invitationToken, $user);

            if ($accepted) {
                return redirect()->intended(route('dashboard', absolute: false))
                    ->with('success', 'Welcome! You have successfully joined the workspace.');
            } else {
                // If invitation acceptance fails, still proceed with normal registration
                // but show a warning message
                return redirect()->intended(route('dashboard', absolute: false))
                    ->with('warning', 'Account created successfully, but could not join workspace. The invitation may be invalid or expired.');
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
