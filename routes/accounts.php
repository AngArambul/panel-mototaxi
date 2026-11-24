<?php

use App\Http\Controllers\AccountController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'can:viewAny,'.User::class])->prefix('usuarios')->name('accounts.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/crear', [AccountController::class, 'create'])->can('create', User::class)->name('create');
    Route::post('/', [AccountController::class, 'store'])->can('create', User::class)->name('store');
    Route::get('/{account}/editar', [AccountController::class, 'edit'])->whereUuid('account')->can('update', 'account')->name('edit');
    Route::put('/{account}', [AccountController::class, 'update'])->whereUuid('account')->can('update', 'account')->name('update');
    Route::delete('/{account}', [AccountController::class, 'deactivate'])->whereUuid('account')->can('update', 'account')->name('destroy');
    Route::patch('/{account}/activar', [AccountController::class, 'activate'])->whereUuid('account')->can('update', 'account')->name('activate');
});
