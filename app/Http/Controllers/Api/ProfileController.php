<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->currentAccessToken() instanceof PersonalAccessToken, 401, 'Usa un token Bearer.');
        abort_unless($user->tokenCan('perfil:leer'), 403, 'El token no tiene el permiso perfil:leer.');

        return response()->json([
            'id_usuario' => $user->id_usuario,
            'nombre' => $user->nombre,
            'rol' => $user->rol,
        ])->header('Cache-Control', 'no-store');
    }
}
