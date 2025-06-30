<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    // Workspace settings routes
    Route::get('settings/workspace', [WorkspaceController::class, 'show'])->name('settings.workspace');
    Route::patch('settings/workspace', [WorkspaceController::class, 'update'])->name('settings.workspace.update');
    Route::post('settings/workspace/invite', [WorkspaceController::class, 'inviteUser'])->name('settings.workspace.invite');
    Route::patch('settings/workspace/members/{membership}', [WorkspaceController::class, 'updateMemberRole'])->name('settings.workspace.members.update');
    Route::delete('settings/workspace/members/{membership}', [WorkspaceController::class, 'removeMember'])->name('settings.workspace.members.remove');
    Route::delete('settings/workspace/invitations/{invitation}', [WorkspaceController::class, 'cancelInvitation'])->name('settings.workspace.invitations.cancel');
});
