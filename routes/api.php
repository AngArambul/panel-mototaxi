<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 1. Meta verifica que el servidor es tuyo (GET)
Route::get('/webhook/whatsapp', [ChatbotController::class, 'verifyWebhook']);

// 2. Meta te envía los mensajes reales de los clientes (POST)
Route::post('/webhook/whatsapp', [ChatbotController::class, 'webhook']);