<?php

use App\Http\Controllers\AppController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/apps/{app}', [AppController::class, 'showDetails'])->name('apps.show');
});

require __DIR__ . '/api.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
