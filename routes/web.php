<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DispatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/dispatch/login', [DispatchController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/dispatch/data', [DispatchController::class, 'data']);
    Route::get('/dispatch/integration', [DispatchController::class, 'integration']);
    Route::post('/dispatch/trips', [DispatchController::class, 'createTrip']);
    Route::post('/dispatch/invitation', [DispatchController::class, 'invitation']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
Route::view('/legal', 'cumplimiento');
