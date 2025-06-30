<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceSwitchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Workspace invitation routes (public access)
Route::get('invitation/{token}', [WorkspaceInvitationController::class, 'show'])->name('workspace.invitation.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/apps/{app}', [AppController::class, 'showDetails'])->name('apps.show');

    // Workspace switching
    Route::post('/workspace/switch', [WorkspaceSwitchController::class, 'switch'])->name('workspace.switch');
});

require __DIR__.'/api.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
