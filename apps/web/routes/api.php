<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\InstallationController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

// Public API Routes (no authentication required)
Route::group(['prefix' => 'api/v1'], function () {
    // Public installation endpoint for external app integrations
    Route::post('installations', [InstallationController::class, 'store'])->middleware('throttle:2,1')->withoutMiddleware([VerifyCsrfToken::class])->name('api.installations.store');
});

// Authenticated API Routes (for UI integration)
Route::middleware(['auth', 'verified'])->group(function () {
    // API Version 1
    Route::group(['prefix' => 'api/v1'], function () {

        // App Management Routes
        Route::group(['prefix' => 'apps'], function () {
            Route::get('/', [AppController::class, 'index'])->name('api.apps.index');
            Route::get('/{app}', [AppController::class, 'show'])->name('api.apps.show');
            Route::post('/', [AppController::class, 'store'])->name('api.apps.store');
            Route::put('/{app}', [AppController::class, 'update'])->name('api.apps.update');
            Route::delete('/{app}', [AppController::class, 'destroy'])->name('api.apps.destroy');
        });

        // Installation Management Routes
        Route::group(['prefix' => 'installations'], function () {
            // Consolidated route for all installation queries (list, show, aggregate)
            Route::get('/', [InstallationController::class, 'index'])->name('api.installations.index');
        });
    });
});
