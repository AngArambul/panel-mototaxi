<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class DispatchController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['telefono' => ['required', 'regex:/^[0-9]{10}$/'], 'password' => ['required', 'string', 'max:128']]);
        try {
            $response = Http::connectTimeout(3)->timeout(15)->post($this->url('/api/auth/login'), $credentials);
        } catch (ConnectionException $exception) {
            return response()->json(['ok' => false, 'msg' => 'Servidor de despacho inaccesible.'], 503);
        }
        if ($response->failed()) {
            return response()->json(['ok' => false, 'msg' => 'No se pudo iniciar la sesión de despacho.'], $response->status());
        }
        if ($response->json('usuario.rol') !== 'PROPIETARIO') {
            Http::withToken($response->json('token'))->timeout(5)->post($this->url('/api/auth/logout'));
            return response()->json(['ok' => false, 'msg' => 'Se requiere una cuenta de propietario.'], 403);
        }
        $request->session()->regenerate();
        $request->session()->put('dispatch_token', Crypt::encryptString($response->json('token')));
        return response()->json(['ok' => true, 'msg' => 'Flotilla conectada.']);
    }

    public function data(Request $request)
    {
        return $this->proxy($request, 'GET', '/api/unidades');
    }

    public function createTrip(Request $request)
    {
        $request->validate(['origen' => 'required|string|max:300', 'destino' => 'required|string|max:300',
            'numeroUnidad' => 'required|string|max:4', 'pasajero' => 'nullable|string|max:120']);
        return $this->proxy($request, 'POST', '/api/viajes', $request->only('origen', 'destino', 'numeroUnidad', 'pasajero'));
    }

    public function invitation(Request $request)
    {
        $request->validate(['numeroUnidad' => 'required|string|max:4']);
        return $this->proxy($request, 'POST', '/api/unidades/invitacion', $request->only('numeroUnidad'));
    }

    public function integration(Request $request)
    {
        return $this->proxy($request, 'GET', '/api/integraciones/estado');
    }

    private function proxy(Request $request, string $method, string $path, array $data = [])
    {
        if (! $request->session()->has('dispatch_token')) {
            return response()->json(['ok' => false, 'msg' => 'Conecta tu cuenta de propietario.'], 401);
        }
        $token = Crypt::decryptString($request->session()->get('dispatch_token'));
        try {
            $response = Http::withToken($token)->acceptJson()->connectTimeout(3)->timeout(15)->send($method, $this->url($path), ['json' => $data]);
        } catch (ConnectionException $exception) {
            return response()->json(['ok' => false, 'msg' => 'Servidor de despacho inaccesible.'], 503);
        }
        if ($response->status() === 401) {
            $request->session()->forget('dispatch_token');
        }
        return response()->json($response->json() ?: ['ok' => false, 'msg' => 'El servidor no respondió correctamente.'], $response->status());
    }

    private function url(string $path): string
    {
        return rtrim(config('services.dispatch.url'), '/').$path;
    }
}
