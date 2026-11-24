<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileTokenController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'active.account'])->group(function () {
    Route::get('/dashboard', [PanelController::class, 'show'])->middleware('verified')->block(30, 5)->name('dashboard');
    Route::prefix('dispatch')->as('dispatch.')->group(function () {
        Route::post('/login', [DispatchController::class, 'login'])->middleware('throttle:6,1')->block(30, 5)->name('login');
        Route::get('/data', [DispatchController::class, 'data'])->name('data');
        Route::get('/integration', [DispatchController::class, 'integration'])->name('integration');
        Route::post('/trips', [DispatchController::class, 'createTrip'])->block(30, 5)->name('trips.store');
        Route::post('/invitation', [DispatchController::class, 'invitation'])->block(30, 5)->name('invitations.store');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/perfil/tokens', [ProfileTokenController::class, 'index'])->name('profile.tokens.index');
    Route::post('/perfil/tokens', [ProfileTokenController::class, 'store'])->middleware('throttle:6,1')->name('profile.tokens.store');
    Route::delete('/perfil/tokens/{token}', [ProfileTokenController::class, 'destroy'])->whereNumber('token')->name('profile.tokens.destroy');
});

require __DIR__.'/accounts.php';
require __DIR__.'/auth.php';
Route::view('/legal', 'cumplimiento')->name('legal');
