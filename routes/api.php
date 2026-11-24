<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ProfileController;

Route::get('/user', [ProfileController::class, 'show'])
    ->middleware(['auth:sanctum', 'active.account', 'throttle:60,1'])->name('api.profile.show');

// 1. Meta verifica que el servidor es tuyo (GET)
Route::get('/webhook/whatsapp', [ChatbotController::class, 'verifyWebhook'])->name('api.whatsapp.verify');

// 2. Meta te envía los mensajes reales de los clientes (POST)
Route::post('/webhook/whatsapp', [ChatbotController::class, 'webhook'])->name('api.whatsapp.receive');
