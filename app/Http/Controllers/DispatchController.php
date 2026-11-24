<?php

namespace App\Http\Controllers;

use App\Http\Requests\DispatchInvitationRequest;
use App\Http\Requests\DispatchTripRequest;
use App\Services\DispatchClient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DispatchController extends Controller
{
    public function __construct(private DispatchClient $client) {}

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'telefono' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => ['required', 'string', 'max:128'],
        ]);
        $response = $this->client->request('POST', '/api/auth/login', null, $credentials);
        $token = $response?->json('token');

        if ($response === null || $response->serverError()) {
            return $this->result($request, 'Servidor de despacho inaccesible.', 503);
        }
        if (! $response->successful()) {
            return $this->result($request, 'No se pudo iniciar la sesión de despacho.', $response->clientError() ? $response->status() : 502);
        }
        if (! is_string($token) || $token === '') {
            return $this->result($request, 'El servidor no entregó una sesión de despacho.', 502);
        }
        if ($response->json('usuario.rol') !== 'PROPIETARIO') {
            $this->client->request('POST', '/api/auth/logout', $token);
            return $this->result($request, 'Se requiere una cuenta de propietario.', 403);
        }

        $request->session()->regenerate(true);
        $request->session()->put('dispatch_token', Crypt::encryptString($token));

        return $this->result($request, 'Flotilla conectada.');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->proxy($request, 'GET', '/api/unidades');
    }

    public function createTrip(DispatchTripRequest $request): JsonResponse|RedirectResponse
    {
        return $this->submit($request, 'trip', '/api/viajes', $request->validated());
    }

    public function invitation(DispatchInvitationRequest $request): JsonResponse|RedirectResponse
    {
        return $this->submit($request, 'invitation', '/api/unidades/invitacion', $request->validated());
    }

    public function integration(Request $request): JsonResponse
    {
        return $this->proxy($request, 'GET', '/api/integraciones/estado');
    }

    private function submit(Request $request, string $form, string $path, array $data): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            $key = 'dispatch_forms.'.$form;
            $expected = $request->session()->get($key);
            $submitted = $request->input('_submission');
            if (! is_string($expected) || ! is_string($submitted) || ! hash_equals($expected, $submitted)) {
                return $this->result($request, 'El formulario ya se usó o venció. Recarga el panel antes de enviarlo.', 409);
            }
            $request->session()->forget($key);
        }

        $response = $this->proxy($request, 'POST', $path, $data);
        if ($request->expectsJson()) {
            return $response;
        }

        $message = $response->getData()->msg ?? 'Operación completada.';
        $redirect = $this->result($request, $message, $response->getStatusCode());
        if (! $response->isSuccessful()) {
            return $redirect->withInput($data);
        }
        if ($form === 'invitation') {
            $code = $response->getData()->codigoInvitacion ?? null;
            return $redirect->with('dispatch_invitation', $code);
        }
        return $redirect;
    }

    private function proxy(Request $request, string $method, string $path, array $data = []): JsonResponse
    {
        $session = $request->session();
        try {
            $token = $session->has('dispatch_token') ? Crypt::decryptString($session->get('dispatch_token')) : null;
        } catch (DecryptException) {
            $session->forget('dispatch_token');
            $token = null;
        }
        if ($token === null) {
            return response()->json(['ok' => false, 'msg' => 'Conecta tu cuenta de propietario.'], 401);
        }

        $response = $this->client->request($method, $path, $token, $data);
        if ($response === null || $response->serverError()) {
            return response()->json(['ok' => false, 'msg' => 'Servidor de despacho inaccesible.'], 503);
        }
        if ($response->status() === 401) {
            $session->forget('dispatch_token');
        }
        $json = $response->json();
        if ($response->redirect() || ! is_array($json)) {
            return response()->json(['ok' => false, 'msg' => 'El servidor no respondió correctamente.'], 502);
        }

        return response()->json($json, $response->status())->header('Cache-Control', 'no-store');
    }

    private function result(Request $request, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        $success = $status < 300;
        if ($request->expectsJson()) {
            return response()->json(['ok' => $success, 'msg' => $message], $status);
        }

        return to_route('dashboard')->with($success ? 'status' : 'dispatch_error', $message);
    }
}
