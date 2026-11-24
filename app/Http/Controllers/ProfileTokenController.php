<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

class ProfileTokenController extends Controller
{
    public function index(Request $request): Response
    {
        $stored = $request->session()->pull('issued_token');
        try {
            $issuedToken = is_string($stored) ? Crypt::decryptString($stored) : null;
        } catch (DecryptException) {
            $issuedToken = null;
        }

        return response()->view('profile.tokens', [
            'tokens' => $request->user()->tokens()->latest('id')->paginate(20),
            'issuedToken' => $issuedToken,
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'password' => ['required', 'current_password'],
        ], ['password.current_password' => 'La contraseña actual no coincide.']);

        $token = $request->user()->createToken($data['nombre'], ['perfil:leer'], now()->addDays(30));

        return to_route('profile.tokens.index')->with('issued_token', Crypt::encryptString($token->plainTextToken));
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->findOrFail($token)->delete();

        return to_route('profile.tokens.index')->with('status', 'Token revocado.');
    }
}
